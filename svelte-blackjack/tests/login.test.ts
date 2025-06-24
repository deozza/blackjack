import { describe, it, expect, beforeEach, vi } from 'vitest';
import { render, screen } from '@testing-library/svelte';
import userEvent from '@testing-library/user-event';
import fs from 'fs';
import path from 'path';

// Mock the SvelteKit navigation
vi.mock('$app/navigation', () => ({
	goto: vi.fn()
}));

// Since the login page is in (login) group, it should be accessible at /
// But the issue mentions that /login should exist as a separate route
describe('Issue #4: Login page does not exist', () => {
	beforeEach(() => {
		// Clear localStorage before each test
		localStorage.clear();
		vi.clearAllMocks();
	});

	it('should have a login page accessible at /login route', async () => {
		// Test that verifies the /login route exists by checking for the route file
		// According to SvelteKit routing, a /login route would require either:
		// - src/routes/login/+page.svelte
		// - src/routes/login.svelte (legacy, not recommended)
		
		const loginRouteOptions = [
			path.join(process.cwd(), 'src/routes/login/+page.svelte'),
			path.join(process.cwd(), 'src/routes/login.svelte')
		];
		
		const loginRouteExists = loginRouteOptions.some(routePath => {
			try {
				return fs.existsSync(routePath);
			} catch {
				return false;
			}
		});
		
		// This test should pass once the /login route is implemented
		expect(loginRouteExists).toBe(true);
	});

	// it('should redirect to /login when user is disconnected', async () => {
	// 	// Test verifies that disconnected users are redirected to /login
	// 	// The issue mentions: "When you are logged out, there is a redirect to `/` (which is the `/login` page)"
	// 	// This means the app should redirect unauthenticated users to /login
		
	// 	// Mock the goto function to track redirects
	// 	const { goto } = await import('$app/navigation');
	// 	const gotoSpy = vi.mocked(goto);
		
	// 	// Clear localStorage to simulate logged out state
	// 	localStorage.removeItem('token');
	// 	localStorage.removeItem('user');
		
	// 	// Try to access a protected route (like user profile or games)
	// 	// This should trigger a redirect to /login for unauthenticated users
		
	// 	// Check if there's an authentication guard that redirects to /login
	// 	// We can test this by checking hooks.server.ts or hooks.client.ts
	// 	const hooksPath = path.join(process.cwd(), 'src/routes/hooks.ts');
		
	// 	if (fs.existsSync(hooksPath)) {
	// 		const hooksContent = fs.readFileSync(hooksPath, 'utf-8');
			
	// 		// Test should FAIL: Check that hooks redirect to /login for unauthenticated users
	// 		// Currently it redirects to '/' but /login route doesn't exist
	// 		// Look for redirect patterns to /login
	// 		const hasLoginRedirect = hooksContent.includes('redirect(') && 
	// 		                         (hooksContent.includes("'/login'") || hooksContent.includes('"/login"'));
			
	// 		expect(hasLoginRedirect).toBe(true); // SHOULD FAIL - redirects to '/' instead of '/login'
	// 	}
		
	// 	// Alternative: Check if layout or component handles auth redirects
	// 	const layoutPath = path.join(process.cwd(), 'src/routes/(game)/+layout.svelte');
	// 	if (fs.existsSync(layoutPath)) {
	// 		const layoutContent = fs.readFileSync(layoutPath, 'utf-8');
			
	// 		// Test should FAIL: Layout should redirect to /login when no token
	// 		expect(layoutContent).toMatch(/goto.*['"`]\/login['"`]/); // SHOULD FAIL - redirects to / instead
	// 	}
	// });

	it('should allow navigation from signup page to login page', async () => {
		// Test that verifies the signup page has a link to /login
		// According to the issue, the "Log in" link in /signup redirects to a Not found route
		// This means the /login route doesn't exist
		
		// First check if the signup page exists
		const signupPagePath = path.join(process.cwd(), 'src/routes/(login)/signup/+page.svelte');
		expect(fs.existsSync(signupPagePath)).toBe(true);
		
		// Read the signup page content to verify it has a link to /login
		const signupContent = fs.readFileSync(signupPagePath, 'utf-8');
		
		// Check that the signup page contains a link to /login
		expect(signupContent).toContain('href="/login"');
		
		// This demonstrates that the signup page expects /login to exist
		// but the route file doesn't exist (tested above), causing the 404
	});

	it('should allow registration button to work in signup page', async () => {
		// Test verifies that the "Register" button in /signup works correctly
		// The issue mentions: "Register" button redirects to a Not found route
		// This happens because the signup form likely tries to redirect to /login after registration
		
		const signupPagePath = path.join(process.cwd(), 'src/routes/(login)/signup/+page.svelte');
		
		if (fs.existsSync(signupPagePath)) {
			const signupContent = fs.readFileSync(signupPagePath, 'utf-8');
			
			// Check what happens after successful registration
			// Look for form submission handlers or success redirects
			
			// Test should FAIL: After registration, should redirect to existing route
			// Currently likely redirects to /login which doesn't exist
			const hasLoginRedirect = signupContent.includes('goto("/login")') || 
			                        signupContent.includes("goto('/login')") ||
			                        signupContent.includes('href="/login"');
			
			if (hasLoginRedirect) {
				// If signup tries to redirect to /login, that route must exist
				const loginRouteExists = fs.existsSync(path.join(process.cwd(), 'src/routes/login/+page.svelte'));
				expect(loginRouteExists).toBe(true); // SHOULD FAIL - /login route doesn't exist
			}

			console.log({signupContent})
			
			// Check that register button/form exists
			expect(signupContent).toMatch(/type=['"`]submit['"`]/); // Should have submit button
			expect(signupContent).toMatch(/(?:register|sign.?up)/i); // Should mention register/signup
		}
	});
});
