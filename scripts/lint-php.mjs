import { spawnSync } from 'node:child_process';
import { readdir } from 'node:fs/promises';
import path from 'node:path';
import { workspaces } from './workspaces.mjs';

async function lint(directory) {
  for (const entry of await readdir(directory, { withFileTypes: true })) {
    if (['node_modules', 'vendor', 'build'].includes(entry.name)) continue;
    const file = path.join(directory, entry.name);
    if (entry.isDirectory()) await lint(file);
    else if (entry.name.endsWith('.php')) {
      const result = spawnSync('php', ['-l', file], { stdio: 'inherit' });
      if (result.error) throw new Error('PHP CLI is required. Install PHP 8.2+ to run npm run lint:php.');
      if (result.status !== 0) process.exit(result.status || 1);
    }
  }
}

for (const { directory } of await workspaces()) await lint(directory);
