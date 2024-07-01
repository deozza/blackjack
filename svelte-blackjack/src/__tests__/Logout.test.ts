// src/__tests__/Logout.test.ts
import { render, fireEvent } from "@testing-library/svelte";
import Login from "../routes/(login)/+page.svelte";
import Profile from "../routes/(game)/user/profile/+page.svelte";
import { waitFor } from "@testing-library/dom";

test("Déconnexion", async () => {
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
  fireEvent.click(getByTextProfile('Logout'));

  await waitFor(() => {
    expect(window.location.pathname).toBe('/');
  });

  window.location.pathname = '/profile';
  await waitFor(() => {
    expect(window.location.pathname).not.toBe('/profile');
  });
});
