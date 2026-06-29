import { execSync } from 'node:child_process';

/**
 * The in-container SimpleRisk app container name (where db_open() and the schema
 * live). Override for a compose-project suffix etc. via SIMPLERISK_CONTAINER;
 * defaults to the dev-stack name.
 */
export const APP_CONTAINER = process.env.SIMPLERISK_CONTAINER ?? 'simplerisk-app';

/**
 * Run a PHP snippet inside the live app container. Returns stdout; on a non-zero
 * exit the thrown Error carries stderr (and the failing command) for
 * debuggability. Callers needing best-effort behavior should catch.
 */
export function containerPhp(php: string): string {
  return execSync(`podman exec -i ${APP_CONTAINER} php`, {
    input: php,
    stdio: ['pipe', 'pipe', 'pipe'],
  }).toString();
}
