import { build, context } from 'esbuild';
import { readdir, rm } from 'node:fs/promises';
import path from 'node:path';
import { workspaces } from './workspaces.mjs';

const watch = process.argv.includes('--watch');
const packages = watch ? await workspaces() : [{ directory: process.cwd() }];

for (const { directory } of packages) {
  const entries = (await readdir(path.join(directory, 'src')))
    .filter((file) => /\.(js|css)$/.test(file))
    .map((file) => path.join(directory, 'src', file));
  await rm(path.join(directory, 'build'), { recursive: true, force: true });
  const options = {
    entryPoints: entries,
    outdir: path.join(directory, 'build'),
    bundle: true,
    minify: !watch,
    sourcemap: watch,
    target: ['es2020'],
    logLevel: 'info',
  };
  if (watch) {
    const watcher = await context(options);
    await watcher.watch();
  } else {
    await build(options);
  }
}
