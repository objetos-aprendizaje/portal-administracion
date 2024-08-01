// ***********************************************
// This example commands.js shows you how to
// create various custom commands and overwrite
// existing commands.
//
// For more comprehensive examples of custom
// commands please read more here:
// https://on.cypress.io/custom-commands
// ***********************************************
//
//
// -- This is a parent command --
// Cypress.Commands.add('login', (email, password) => { ... })
//
//
// -- This is a child command --
// Cypress.Commands.add('drag', { prevSubject: 'element'}, (subject, options) => { ... })
//
//
// -- This is a dual command --
// Cypress.Commands.add('dismiss', { prevSubject: 'optional'}, (subject, options) => { ... })
//
//
// -- This will overwrite an existing command --
// Cypress.Commands.overwrite('visit', (originalFn, url, options) => { ... })
//import 'cypress-iframe';
import 'cypress-file-upload';
const fs = require('fs');

Cypress.Commands.add('login', (username, password) => {
    cy.intercept('GET', 'https://portalobjetosaprendizaje.devmainjobs.com/login').as('login');
    cy.visit('https://portalobjetosaprendizaje.devmainjobs.com/');
    cy.get('input[name="username"]').type(username);
    cy.get('input[name="password"]').type(password);
    cy.get('button[type="submit"]').click();
    cy.wait('@login');
  });

  Cypress.Commands.add('loginAdmin', () => {
    cy.get('input[id="edit-name"]').type(Cypress.env('autor'));
    cy.get('input[id="edit-pass"]').type(Cypress.env('pass'));
    cy.get('button[id="edit-submit"]').click();
    cy.url().should('include', Cypress.env('correct_login')); 
  });


  import 'cypress-file-upload';
  Cypress.Commands.add('upload_file', (fileName, fileType = ' ', selector) => {
  cy.get(selector).then(subject => {
  cy.fixture(fileName, 'base64').then(content => {
  const el = subject[0];
  const testFile = new File([content], fileName, { type: fileType });
  const dataTransfer = new DataTransfer();
  dataTransfer.items.add(testFile);
  el.files = dataTransfer.files;
  });
  });
  });
