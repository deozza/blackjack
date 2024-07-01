// src/__tests__/Login.test.ts
import { render, fireEvent } from "@testing-library/svelte";
import Login from "../routes/(login)/+page.svelte";

test("se connecter à l'application", async () => {
 const { getByLabelText, getByText } = render(Login);

 const usernameInput = getByLabelText('username');
 const passwordInput = getByLabelText('password');
 const submitButton = getByText('Login');

 await fireEvent.input(usernameInput, { target: { value: 'admin' } });
 await fireEvent.input(passwordInput, { target: { value: 'admin' } });
 await fireEvent.click(submitButton);

 expect(window.location.pathname).toBe('/profile');
});
