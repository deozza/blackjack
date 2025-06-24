import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render } from '@testing-library/svelte';
import UserProfilePage from '../src/routes/(game)/user/profile/+page.svelte';

// Mock the SvelteKit modules
vi.mock('$app/navigation', () => ({
	goto: vi.fn()
}));

describe('Issue #8: user is null in (game)/user/profile/+page.svelte', () => {
	let consoleErrors: string[] = [];
	
	beforeEach(() => {
		// Clear localStorage and set up token
		localStorage.clear();
		localStorage.setItem('token', 'fake-token');
		
		// Capture console errors to detect "user is null" errors
		consoleErrors = [];
		const originalError = console.error;
		console.error = vi.fn((...args) => {
			consoleErrors.push(args.join(' '));
			originalError(...args);
		});
		
		vi.clearAllMocks();
	});

	it('should display user profile information instead of being stuck in infinite loading', async () => {
		// Mock successful API response with user data
		global.fetch = vi.fn().mockResolvedValueOnce({
			status: 200,
			ok: true,
			json: () => Promise.resolve({
				id: 1,
				username: 'testuser',
				email: 'test@example.com',
				wallet: 1000
			})
		});

		const { container } = render(UserProfilePage);

		// Wait for async loading
		await new Promise(resolve => setTimeout(resolve, 200));

		// Test should FAIL: When bug is present, page shows infinite loading
		// The page should show user profile, not be stuck on "Loading..."
		expect(container.textContent).not.toBe('Loading...'); // SHOULD FAIL - stuck in loading
		
		// Should display welcome message with username
		expect(container.textContent).toContain('Welcome testuser !'); // SHOULD FAIL - user is null
		
		// Should show user data in form fields
		expect(container.textContent).toContain('Username:'); // SHOULD FAIL - no content shown
		expect(container.textContent).toContain('Email:'); // SHOULD FAIL - no content shown
		expect(container.textContent).toContain('Wallet:'); // SHOULD FAIL - no content shown
	});

	it('should show user form fields with actual data', async () => {
		// Mock API response
		global.fetch = vi.fn().mockResolvedValueOnce({
			status: 200,
			ok: true,
			json: () => Promise.resolve({
				id: 1,
				username: 'john_doe',
				email: 'john@example.com',
				wallet: 500
			})
		});

		const { container } = render(UserProfilePage);

		// Wait for loading
		await new Promise(resolve => setTimeout(resolve, 200));

		// Test should FAIL: When bug is present, inputs are empty or cause errors
		// Should have input fields with user data
		const usernameInput = container.querySelector('input[name="username"]') as HTMLInputElement;
		const emailInput = container.querySelector('input[name="email"]') as HTMLInputElement;
		const walletInput = container.querySelector('input[name="wallet"]') as HTMLInputElement;
		
		expect(usernameInput).toBeTruthy(); // SHOULD FAIL - form not rendered
		expect(emailInput).toBeTruthy(); // SHOULD FAIL - form not rendered
		expect(walletInput).toBeTruthy(); // SHOULD FAIL - form not rendered
		
		// Should have actual user data, not be empty
		expect(usernameInput?.value).toBe('john_doe'); // SHOULD FAIL - user is null
		expect(emailInput?.value).toBe('john@example.com'); // SHOULD FAIL - user is null
		expect(walletInput?.value).toBe('500'); // SHOULD FAIL - user is null
	});

	it('should not produce "user is null" errors in console', async () => {
		// Mock API response
		global.fetch = vi.fn().mockResolvedValueOnce({
			status: 200,
			ok: true,
			json: () => Promise.resolve({
				id: 1,
				username: 'testuser',
				email: 'test@example.com',
				wallet: 250
			})
		});

		render(UserProfilePage);

		// Wait for component to render and any reactive statements to execute
		await new Promise(resolve => setTimeout(resolve, 300));

		// Test should FAIL: When bug is present, console shows "user is null" errors
		expect(consoleErrors).toHaveLength(0); // SHOULD FAIL - errors present
		
		// Specifically check for user null errors
		const userNullErrors = consoleErrors.filter(error => 
			error.includes('user is null') || 
			error.includes('Cannot read properties of null') ||
			error.includes('null')
		);
		expect(userNullErrors).toHaveLength(0); // SHOULD FAIL - this error occurs
	});

	it('should not be stuck in infinite loading state', async () => {
		// Mock successful API response
		global.fetch = vi.fn().mockResolvedValueOnce({
			status: 200,
			ok: true,
			json: () => Promise.resolve({
				id: 1,
				username: 'testuser',
				email: 'test@example.com',
				wallet: 100
			})
		});

		const { container } = render(UserProfilePage);

		// Wait for async operations
		await new Promise(resolve => setTimeout(resolve, 500));

		// Test should FAIL: When bug is present, page remains in loading state
		// The page should eventually show content, not be stuck loading forever
		const textContent = container.textContent?.trim() || '';
		
		expect(textContent).not.toBe('Loading...'); // SHOULD FAIL - infinite loading
		expect(textContent.length).toBeGreaterThan(20); // SHOULD FAIL - no meaningful content
		
		// Should show actual profile content
		expect(textContent).toContain('Welcome'); // SHOULD FAIL - stuck in loading
	});

	it('should render without throwing errors when API returns valid user data', async () => {
		// Mock successful user data response
		global.fetch = vi.fn().mockResolvedValueOnce({
			status: 200,
			ok: true,
			json: () => Promise.resolve({
				id: 1,
				username: 'validuser',
				email: 'valid@example.com',
				wallet: 750
			})
		});

		let renderError = null;
		
		try {
			const { container } = render(UserProfilePage);
			
			// Wait for async operations
			await new Promise(resolve => setTimeout(resolve, 400));
			
			// Component should render successfully
			expect(container).toBeDefined();
		} catch (error) {
			renderError = error;
		}

		// Test should FAIL: When bug is present, component crashes due to null access
		expect(renderError).toBeNull(); // SHOULD FAIL - component throws error accessing null.property
	});
});
