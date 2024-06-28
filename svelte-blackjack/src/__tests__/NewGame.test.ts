// src/__tests__/NewGame.test.ts
import { render, fireEvent } from "@testing-library/svelte";
import Login from "../routes/(login)/+page.svelte";
import Profile from "../routes/(game)/user/profile/+page.svelte";
import MyGames from "../routes/(game)/games/+page.svelte";
import { waitFor } from "@testing-library/dom";

test("Lancement d'une nouvelle partie", async () => {
  const { getByLabelText, getByText } = render(Login);

  const usernameInput = getByLabelText('username');
  const passwordInput = getByLabelText('password');
  const submitButton = getByText('Login');

  await fireEvent.input(usernameInput, { target: { value: 'admin' } });
  await fireEvent.input(passwordInput, { target: { value: 'admin' } });
  await fireEvent.click(submitButton);

  await waitFor(() => {
    expect(window.location.pathname).toBe('/profile');
  });

  const { getByText: getByTextProfile } = render(Profile);
  fireEvent.click(getByTextProfile('My games'));

  const { getByText: getByTextGames } = render(MyGames);
  fireEvent.click(getByTextGames('New game'));

  await waitFor(() => {
    expect(getByTextGames('Game started')).toBeInTheDocument();
  });
});
