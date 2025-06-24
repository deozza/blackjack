import { Builder, By, Key, until } from 'selenium-webdriver';

const BASE_URL = 'http://127.0.0.1:5173/login';

const driver = await new Builder().forBrowser('MicrosoftEdge').build();

try {
    console.log('Ouverture de la page de login...');
    await driver.get(BASE_URL);

    console.log('Remplissage du formulaire...');
    await driver.findElement(By.name('username')).sendKeys('testUser');
    await driver.findElement(By.name('password')).sendKeys('secret', Key.RETURN);

    console.log('Attente de la redirection...');
    await driver.wait(until.urlContains('/user/profile'), 5000);

    console.log('Test réussi : utilisateur connecté et redirigé vers le profil !');
} catch (err) {
    console.error('Test échoué :', err.message);
} finally {
    await driver.quit();
}
