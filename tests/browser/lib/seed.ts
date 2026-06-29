import type { Page } from '@playwright/test';
import { apiPost, unique } from './auth';
import { containerPhp } from './container';
import { customFieldIdByName } from './db';

export interface AuditTarget {
  fwId: number;
  ctrlId: number;
  testId: number;
}

/**
 * Extract + validate a created entity id from an apiPost response. The CRUD
 * creates return {status:201, data:{id}}; a failed create (perms/validation/409
 * or a non-JSON body) throws with the API response so the failure names the real
 * cause instead of surfacing later as a TypeError on undefined.data.id. Matches
 * the PHPUnit ComplianceSeedTrait's assertSame(201, $code, ...).
 */
function createdId(res: { status: number; json: any }, what: string): number {
  const id = res.status === 201 ? res.json?.data?.id : undefined;
  if (id === undefined) {
    throw new Error(
      `seed: ${what} create failed (HTTP ${res.status}): ${JSON.stringify(res.json)}`,
    );
  }
  return Math.floor(id);
}

/**
 * Seed a framework + control + test + the framework->control mapping so the
 * audit_initiation tree shows a root framework row with a lazily-loaded child.
 *
 * The CRUD steps go over /api/v2 (apiPost handles the csrf token). The control->
 * framework mapping has no clean CRUD endpoint (createControlCrud sets
 * map_frameworks=[]), so we insert the framework_control_mappings row directly
 * via the in-container PHP — this is what makes the framework appear in the
 * initiate_audits tree (get_initiate_frameworks_by_filter: status=1 AND a mapped
 * control with a test, compliance.php:3058).
 *
 * Cleanup is handled by the shared sweepE2E() afterEach (E2E_-prefixed names).
 */
export async function seedAuditTarget(page: Page, tag = 'AUD'): Promise<AuditTarget> {
  const u = unique(tag);
  const fwId = createdId(await apiPost(page, '/api/v2/governance/frameworks', { name: `${u}_FW` }), 'framework');
  const ctrlId = createdId(await apiPost(page, '/api/v2/governance/controls', { short_name: `${u}_CTRL` }), 'control');
  const testId = createdId(await apiPost(page, '/api/v2/compliance/tests', {
    name: `${u}_TEST`,
    framework_control_id: ctrlId,
  }), 'test');

  // ctrlId/fwId are int-coerced (createdId), safe to interpolate into the PHP
  // expression; the values still bind via ? placeholders at the SQL level.
  containerPhp(
    `<?php require '/var/www/simplerisk/includes/functions.php'; $db=db_open(); $db->prepare("INSERT INTO framework_control_mappings (control_id, framework, reference_name) VALUES (?, ?, 'E2E')")->execute([${ctrlId}, ${fwId}]); db_close($db);`,
  );

  return { fwId, ctrlId, testId };
}

// ---------------------------------------------------------------------------
// Separation / Org-Hierarchy seed helpers (team / business-unit membership).
// Names are E2E_-prefixed (via unique()) so sweepE2E() reclaims them. Each id is
// the auto-increment PK returned by lastInsertId().
// ---------------------------------------------------------------------------

/** Insert a team, return its id (team.value auto-increments). */
export function seedTeam(tag = 'TEAM'): number {
  const nameJson = JSON.stringify(unique(tag));
  const id = parseInt(
    containerPhp(`<?php require '/var/www/simplerisk/includes/functions.php'; $db=db_open(); $db->prepare("INSERT INTO team (name) VALUES (?)")->execute([${nameJson}]); echo (int)$db->lastInsertId(); db_close($db);`),
    10,
  ) || 0;
  if (!id) throw new Error('seedTeam: insert failed');
  return id;
}

/** Link a user to a team (INSERT IGNORE so re-runs are idempotent). */
export function assignUserToTeam(uid: number, teamId: number): void {
  containerPhp(
    `<?php require '/var/www/simplerisk/includes/functions.php'; $db=db_open(); $db->prepare("INSERT IGNORE INTO user_to_team (user_id, team_id) VALUES (?, ?)")->execute([${uid | 0}, ${teamId | 0}]); db_close($db);`,
  );
}

/** Link a risk (DB id) to a team. */
export function assignRiskToTeam(riskDbId: number, teamId: number): void {
  containerPhp(
    `<?php require '/var/www/simplerisk/includes/functions.php'; $db=db_open(); $db->prepare("INSERT IGNORE INTO risk_to_team (risk_id, team_id) VALUES (?, ?)")->execute([${riskDbId | 0}, ${teamId | 0}]); db_close($db);`,
  );
}

/** Insert a business unit and link the given teams to it; return the BU id. */
export function seedBusinessUnit(tag = 'BU', teamIds: number[] = []): number {
  const nameJson = JSON.stringify(unique(tag));
  const id = parseInt(
    containerPhp(`<?php require '/var/www/simplerisk/includes/functions.php'; $db=db_open(); $db->prepare("INSERT INTO business_unit (name, description) VALUES (?, 'E2E')")->execute([${nameJson}]); $bu=(int)$db->lastInsertId(); foreach (json_decode('${JSON.stringify(teamIds)}', true) as $t) { $db->prepare("INSERT IGNORE INTO business_unit_to_team (business_unit_id, team_id) VALUES (?, ?)")->execute([$bu, (int)$t]); } echo $bu; db_close($db);`),
    10,
  ) || 0;
  if (!id) throw new Error('seedBusinessUnit: insert failed');
  return id;
}

/**
 * Create a custom field via the real admin POST (PRG 302) and return its id.
 * Mirrors the PHPUnit CustomizationExtraRiskFieldTest. Requires the customization
 * extra to be enabled for the page's session.
 */
export async function createCustomFieldAdmin(page: Page, name: string): Promise<number> {
  await apiPost(page, '/admin/customization.php', {
    create_field: 1, fgroup: 'risk', name, type: 'text', panel_name: 'left',
  });
  const id = customFieldIdByName(name);
  if (!id) throw new Error(`custom field '${name}' was not created by the admin POST`);
  return id;
}
