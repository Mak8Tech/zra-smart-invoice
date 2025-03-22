import '@testing-library/jest-dom';
import { vi, beforeAll, afterAll } from 'vitest';

// Mock InertiaJS
vi.mock('@inertiajs/react', () => ({
  Head: vi.fn(() => null),
  Link: vi.fn(({ children }) => children),
  useForm: vi.fn(() => ({
    data: {},
    setData: vi.fn(),
    post: vi.fn(),
    processing: false,
    errors: {},
    reset: vi.fn(),
  })),
}));

// Global beforeAll hook
beforeAll(() => {
  // Add any global test setup here
  console.log('Starting ZRA Smart Invoice frontend tests');
});

// Global afterAll hook
afterAll(() => {
  // Add any global test teardown here
  console.log('Completed ZRA Smart Invoice frontend tests');
});
