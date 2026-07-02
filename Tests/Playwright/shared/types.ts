export interface BackendLoginCredentials {
  username: string;
  password: string;
}

export interface FrontendLoginCredentials {
  username: string;
  password: string;
}

export interface Typo3TestConfig {
  baseUrl: string;
  backendUrl: string;
  login: {
    backend?: BackendLoginCredentials;
    frontend?: FrontendLoginCredentials;
  };
  storageStatePath?: string;
}
