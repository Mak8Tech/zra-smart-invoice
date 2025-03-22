import '@testing-library/jest-dom';

// Extend the expect interface with jest-dom types
declare global {
  namespace Vi {
    interface Assertion {
      toBeInTheDocument(): void;
      toHaveLength(length: number): void;
    }
  }
}
