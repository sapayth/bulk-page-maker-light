/**
 * Custom file watcher for macOS to avoid fsevents issues
 */
const chokidar = require('chokidar');
const { exec } = require('child_process');
const path = require('path');

// Paths to watch
const watchPaths = [
  path.resolve(__dirname, '../src/**/*.js'),
  path.resolve(__dirname, '../src/**/*.css'),
  path.resolve(__dirname, '../src/**/*.jsx')
];

// Ignore patterns
const ignoredPaths = [
  '**/node_modules/**',
  '**/build/**'
];

console.log('Starting watch process with polling...');

// Initialize watcher
const watcher = chokidar.watch(watchPaths, {
  ignored: ignoredPaths,
  ignoreInitial: true,
  usePolling: true,  // Use polling instead of native file watching
  interval: 1000,    // Poll every 1000ms
  awaitWriteFinish: {  // Wait until the file is fully written
    stabilityThreshold: 300,
    pollInterval: 100
  }
});

// Flag to prevent multiple concurrent builds
let isBuilding = false;

// Function to run webpack build
const runBuild = () => {
  if (isBuilding) {
    console.log('Build already in progress, skipping...');
    return;
  }
  
  isBuilding = true;
  console.log('File change detected! Building...');
  
  exec('npx wp-scripts build', (error, stdout, stderr) => {
    if (error) {
      console.error(`Error: ${error.message}`);
      isBuilding = false;
      return;
    }
    if (stderr) {
      console.error(`Stderr: ${stderr}`);
    }
    console.log(`Build complete: ${stdout}`);
    isBuilding = false;
  });
};

// Watch for changes
watcher
  .on('change', path => {
    console.log(`File changed: ${path}`);
    runBuild();
  })
  .on('add', path => {
    console.log(`File added: ${path}`);
    runBuild();
  })
  .on('unlink', path => {
    console.log(`File removed: ${path}`);
    runBuild();
  })
  .on('error', error => console.error(`Watcher error: ${error}`));

console.log('Watching for file changes...'); 