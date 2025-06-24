import { Builder, By, until } from 'selenium-webdriver';

const BASE_URL = 'http://127.0.0.1:5173/signup';

const driver = await new Builder().forBrowser('MicrosoftEdge').build();

try {
    console.log('Ouverture de la page signup...');
    await driver.get(BASE_URL);

    await driver.wait(until.elementLocated(By.id('username')), 5000);
    await driver.wait(until.elementLocated(By.id('email')), 5000);
    await driver.wait(until.elementLocated(By.id('password')), 5000);

    const random = Math.floor(Math.random() * 10000);
    await driver.findElement(By.id('username')).sendKeys('testuser' + random);
    await driver.findElement(By.id('email')).sendKeys(`test${random}@example.com`);
    await driver.findElement(By.id('password')).sendKeys('StrongPassword123!');

    const registerButton = await driver.findElement(By.css('button[type="submit"]'));
    await registerButton.click();

    console.log('En attente de redirection...');
    await driver.wait(until.urlContains('/login'), 5000);

    console.log('Test réussi : redirection vers /login détectée.');

} catch (err) {
    console.error('Test échoué :', err.message);
} finally {
    await driver.quit();
}
