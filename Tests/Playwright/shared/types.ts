export interface BackendLoginCredentials {
  username: string;
  password: string;
}

export interface FrontendLoginCredentials {
  username: string;
  password: string;
}

export interface DbConfig {
  host: string;
  port: number;
  user: string;
  password: string;
  database: string;
}

export interface Typo3TestConfig {
  baseUrl: string;
  backendUrl: string;
  login: {
    backend?: BackendLoginCredentials;
    frontend?: FrontendLoginCredentials;
  };
  db?: DbConfig;
  storageStatePath?: string;
}

export interface EnvironmentResetOptions {
  command: string;
  cwd?: string;
  skipInCi?: boolean;
}
