// Import the default webpack config from @wordpress/scripts
const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');

module.exports = {
  ...defaultConfig,
  
  // Explicitly set the mode 
  mode: process.env.NODE_ENV === 'production' ? 'production' : 'development',
  
  // Watch mode settings
  watchOptions: {
    // Force use of polling (no fsevents)
    poll: 1000,
    // Ignore node_modules
    ignored: /node_modules/,
    // Add delay before rebuilding
    aggregateTimeout: 500,
  },
  
  // Explicitly disable fsevents
  snapshot: {
    managedPaths: [path.resolve(__dirname, 'node_modules')],
  },
  
  // Prevent webpack from watching too many files
  stats: {
    all: false,
    assets: true,
    errors: true,
    errorDetails: true,
    warnings: true,
  }
}; 