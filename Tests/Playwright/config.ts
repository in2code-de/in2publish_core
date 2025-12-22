export default {
  // For the sake of using relative urls in page.goto('module/...') the trailing slash is needed
  local: {
    baseUrl: process.env.PLAYWRIGHT_BASE_URL || 'https://local.v13.in2publish-core.de/typo3/',
  },
  foreign: {
    baseUrl: process.env.PLAYWRIGHT_FOREIGN_BASE_URL || 'https://foreign.v13.in2publish-core.de/typo3/',
  },
  login: {
    admin: {
      username: process.env.TYPO3_BACKEND_ADMIN_USERNAME || 'admin',
      password: process.env.TYPO3_BACKEND_ADMIN_PASSWORD || 'password'
    }
  }
};
