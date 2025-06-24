import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest';
import { render, screen, waitFor, cleanup } from '@testing-library/svelte';
import userEvent from '@testing-library/user-event';
import GamesPage from '../src/routes/(game)/user/games/+page.svelte';

// Mock the SvelteKit navigation
vi.mock('$app/navigation', () => ({
	goto: vi.fn()
}));

// Mock fetch for API calls
global.fetch = vi.fn();

describe('Issue #6: Bad ID displayed in the table in /user/games', () => {
	beforeEach(() => {
		// Clear localStorage and mocks before each test
		localStorage.clear();
		vi.clearAllMocks();
		
		// Set up a fake token
		localStorage.setItem('token', 'fake-token');
	});

	afterEach(() => {
		cleanup();
	});

	it('should display the correct game ID in the table, not the array index', async () => {
		// Mock API response with games that have specific IDs
		const mockGames = [
			{ id: 'game-abc-123', status: 'playing' },
			{ id: 'game-def-456', status: 'finished' },
			{ id: 'game-ghi-789', status: 'playing' }
		];

		// Mock the fetch call to return our test data
		(global.fetch as any).mockResolvedValueOnce({
			status: 200,
			json: () => Promise.resolve(mockGames)
		});

		// Render the games page
		render(GamesPage);

		// Wait for the component to load the games
		await waitFor(() => {
			expect(screen.getByText('My games')).toBeTruthy();
		});

		// Test should FAIL: Check that the actual game IDs are displayed
		// These expectations will fail because the component shows i+1 instead
		expect(screen.queryByText('game-abc-123')).toBeTruthy(); // SHOULD FAIL - actual ID not shown
		expect(screen.queryByText('game-def-456')).toBeTruthy(); // SHOULD FAIL - actual ID not shown
		expect(screen.queryByText('game-ghi-789')).toBeTruthy(); // SHOULD FAIL - actual ID not shown
	});

	it('should use the correct game ID in resume links, not the array index', async () => {
		// Mock API response with only playing games to get resume links
		const mockGames = [
			{ id: 'game-abc-123', status: 'playing' },
			{ id: 'game-def-456', status: 'playing' }
		];

		(global.fetch as any).mockResolvedValueOnce({
			status: 200,
			json: () => Promise.resolve(mockGames)
		});

		render(GamesPage);

		await waitFor(() => {
			expect(screen.getByText('My games')).toBeTruthy();
		});

		// Test should FAIL: Check that resume links use the correct game IDs
		const resumeLinks = screen.getAllByText('Resume');
		
		// These expectations will fail because the links use i+1 instead of actual IDs
		expect(resumeLinks[0].closest('a')?.getAttribute('href')).toBe('/user/games/game-abc-123'); // SHOULD FAIL
		expect(resumeLinks[1].closest('a')?.getAttribute('href')).toBe('/user/games/game-def-456'); // SHOULD FAIL
	});

	it('should handle empty games list correctly', async () => {
		// Mock API response with no games
		(global.fetch as any).mockResolvedValueOnce({
			status: 200,
			json: () => Promise.resolve([])
		});

		render(GamesPage);

		await waitFor(() => {
			expect(screen.getByText('No games found')).toBeTruthy();
		});

		// Ensure no game IDs are shown when there are no games
		expect(screen.queryByText('1')).toBeNull();
		expect(screen.queryByText('2')).toBeNull();
		expect(screen.queryByText('3')).toBeNull();
	});
});
