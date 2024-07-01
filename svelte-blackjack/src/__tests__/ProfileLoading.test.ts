// src/__tests__/ProfileLoading.test.ts
import { render, fireEvent } from "@testing-library/svelte";
import Login from "../routes/(login)/+page.svelte";
import Profile from "../routes/(game)/user/profile/+page.svelte";
import { waitFor } from "@testing-library/dom";

test("Affichage des informations de l'utilisateur", async () => {
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
  expect(getByTextProfile('loading')).toBeInTheDocument();
});
