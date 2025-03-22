/// <reference types="vitest" />
/// <reference types="@testing-library/jest-dom" />

// Declare Vitest global context
interface ImportMeta {
  readonly env: {
    readonly VITE_APP_TITLE: string;
    // Add other environment variables as needed
    [key: string]: string | undefined;
  };
}
