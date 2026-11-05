import { readdir, readFile } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

export const root = fileURLToPath(new URL('../', import.meta.url));

export async function workspaces() {
  const result = [];
  for (const type of ['themes', 'plugins']) {
    for (const entry of await readdir(path.join(root, type), { withFileTypes: true })) {
      if (!entry.isDirectory()) continue;
      const directory = path.join(root, type, entry.name);
      const manifest = JSON.parse(await readFile(path.join(directory, 'package.json'), 'utf8'));
      result.push({ directory, manifest });
    }
  }
  return result;
}
