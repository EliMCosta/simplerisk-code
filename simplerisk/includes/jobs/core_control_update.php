<?php

/* This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/. */

return [
    'type' => 'core_control_update',

    'on_failure' => function(PDO $db, array $promise, string $error_message, int $attempts) {
        if (($promise['status'] ?? null) === 'canceled') {
            return;
        }

        $payload = json_decode($promise['payload'], true) ?? [];
        $control_id = $payload['control_id'] ?? null;
        if (!$control_id) return;

        write_debug_log(
            "Control Update: Marked control {$control_id} as failed after {$attempts} attempts: {$error_message}",
            "error"
        );
    },

    'on_success' => function(PDO $db, array $final_promise) {
        // Get the payload
        $payload = json_decode($final_promise['payload'], true) ?? [];

        $control_id = $payload['control_id'] ?? null;

        if ($control_id) write_debug_log("Control Update: Successfully processed control #{$control_id}", "info");
    },

    'task_check' => function(PDO $db) {
        $stmt = $db->prepare("
            SELECT DISTINCT fc.id
            FROM framework_controls fc
            LEFT JOIN queue_tasks qt
                ON qt.task_type = 'core_control_update'
                AND qt.status IN ('pending','in_progress')
                AND CAST(JSON_EXTRACT(qt.payload, '$.control_id') AS UNSIGNED) = fc.id
            WHERE 
              fc.keywords IS NULL
              AND fc.keyword_count = 0
              AND fc.keyword_processing_error = 0
              AND qt.id IS NULL
        ");
        $stmt->execute();
        $control_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($control_ids as $control_id) {
            $queue_task_payload = [
                'triggered_at' => time(),
                'control_id' => (int)$control_id,
                'refresh' => true,
            ];
            queue_task($db, 'core_control_update', $queue_task_payload, 25, 5, 3600);
        }

        return !empty($control_ids);
    },

    'queue_check' => function(array $task, PDO $db) {
        $payload = json_decode($task['payload'], true) ?? [];
        $control_id = $payload['control_id'] ?? null;
        if (!$control_id) return false;

        $stages = [
            'fetch_control',
            'calculate_keywords',
            'calculate_tfidf',
            'clean_tmp_files',
        ];

        $prev_promise_id = null;
        foreach ($stages as $stage_name) {
            $prev_promise_id = create_stage_promise(
                'core_control_update',
                $stage_name,
                $payload,
                $prev_promise_id,
                $control_id,
                $task['id'],
                $db
            );
        }

        queue_update_status($task['id'], 'in_progress', $db);
        return true;
    },

    'stages' => [
        'fetch_control' => function(array $promise, PDO $db) {
            $payload = json_decode($promise['payload'], true) ?? [];
            $control_id = $payload['control_id'] ?? null;

            try
            {
                $stmt = $db->prepare("SELECT * FROM framework_controls WHERE id = :id");
                $stmt->execute([':id' => $control_id]);
                $control = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$control) {
                    cancel_control_task(
                        $db,
                        $promise,
                        'Control deleted before fetch_control'
                    );
                    return $payload;
                }

                $control_ref = save_tmp_data($db, "control_{$control_id}", $control);

                $payload['control_ref'] = $control_ref;
                write_debug_log("[fetch_control] Control Update: Fetched and saved control {$control_id} as tmp data {$control_ref}", "debug");
            }  catch (Exception $e) {
                write_debug_log("[fetch_control] Failed to fetch control for #{$promise['id']}: " . $e->getMessage(), "error");
                throw $e;
            }

            return $payload;
        },

        'calculate_keywords' => function(array $promise, PDO $db) {
            $payload = json_decode($promise['payload'], true) ?? [];
            $control_id = $payload['control_id'] ?? null;
            $control_ref = $payload['control_ref'] ?? null;

            if (!$control_ref || !$control_id) throw new Exception("Missing control_id or control_ref");

            try
            {
                // Check if the control still exists
                $stmt = $db->prepare("SELECT 1 FROM framework_controls WHERE id = :id");
                $stmt->execute([':id' => $control_id]);
                $exists = (bool)$stmt->fetchColumn();
                if (!$exists) {
                    cancel_control_task(
                        $db,
                        $promise,
                        'Control deleted during calculate_keywords'
                    );
                    return $payload; // graceful, terminal exit
                }

                // Load the saved control data
                $control = load_tmp_data($db, $control_ref);

                write_debug_log("Analyzing the contents of Control ID: " . $control_id, "debug");

                // Get the control text and calculate the control term frequency
                $control_text = "{$control['short_name']}: {$control['description']}";
                write_debug_log("[calculate_keywords] Calculating significant terms from the control.  This may take a while.", "debug");
                $keywords = extractSignificantTerms($control_text);
                $keywords_json = json_encode($keywords);
                write_debug_log("[calculate_keywords] Significant Terms: {$keywords_json}", "debug");
                $keyword_count = array_sum($keywords);
                write_debug_log("[calculate_keywords] Keyword count for Control ID {$control_id}: {$keyword_count}", "debug");

                // Update the control with the keywords and keyword count
                $stmt = $db->prepare("UPDATE framework_controls SET keywords = :keywords, keyword_count = :keyword_count WHERE id = :control_id");
                $stmt->bindParam(":keywords", $keywords_json, PDO::PARAM_STR);
                $stmt->bindParam(":keyword_count", $keyword_count, PDO::PARAM_INT);
                $stmt->bindParam(":control_id", $control_id, PDO::PARAM_INT);
                $stmt->execute();

                write_debug_log("[calculate_keywords] Calculated keywords for control {$control_id}", "debug");
            } catch (Exception $e) {
                write_debug_log("[calculate_keywords] Failed to calculate keywords for #{$promise['id']}: " . $e->getMessage(), "error");
                throw $e;
            }

            return $payload;
        },

        'calculate_tfidf' => function(array $promise, PDO $db) {
            $payload = json_decode($promise['payload'], true) ?? [];
            $control_id = $payload['control_id'] ?? null;
            if (!$control_id) throw new Exception("Missing control_id");

            try
            {
                // Check if the control still exists
                $stmt = $db->prepare("SELECT 1 FROM framework_controls WHERE id = :id");
                $stmt->execute([':id' => $control_id]);
                $exists = (bool)$stmt->fetchColumn();
                if (!$exists) {
                    cancel_control_task(
                        $db,
                        $promise,
                        'Control deleted during calculate_tfidf'
                    );
                    return $payload; // graceful, terminal exit
                }

                // Calculate the TF-IDF score for the new/updated control
                $matches = compute_document_control_scores([], [$control_id]);

                // Store the matches
                $payload['matches_ref'] = save_tmp_data($db, "matches_{$control_id}", $matches);

                write_debug_log("[calculate_tfidf] Completed TF-IDF calculations for document {$control_id}", "info");
            } catch (Exception $e) {
                write_debug_log("[calculate_tfidf] Failed to calculate keywords for #{$promise['id']}: " . $e->getMessage(), "error");
                throw $e;
            }

            return $payload;
        },

        'clean_tmp_files' => function(array $promise, PDO $db) {
            $payload = json_decode($promise['payload'], true) ?? [];
            $control_ref = $payload['control_ref'] ?? null;
            $matches_ref = array_filter((array)($payload['matches_ref'] ?? []));

            try
            {
                // Delete the control tmp data
                if ($control_ref)
                {
                    delete_tmp_data($db, $control_ref);
                    write_debug_log("[clean_tmp_files] Deleted tmp control_ref: {$control_ref}", "debug");
                }

                foreach ($matches_ref as $ref)
                {
                    if ($ref)
                    {
                        delete_tmp_data($db, $ref);
                        write_debug_log("[clean_tmp_files] Deleted tmp matches_ref: {$ref}", "debug");
                    }
                }

                unset($payload['control_ref']);
                unset($payload['matches_ref']);
            } catch (Exception $e) {
                write_debug_log(
                    "[clean_tmp_files] Error: " . $e->getMessage(),
                    "error"
                );
                return false;
            }

            return $payload;
        },
    ],

    // No task_check needed; queueing is done manually in code
];

?>