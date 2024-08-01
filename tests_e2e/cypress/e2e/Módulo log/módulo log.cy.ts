// <reference types="cypress" />
Cypress.on('uncaught:exception', () => false),
describe('Log', () => {
  beforeEach(() => {
   
    cy.visit(Cypress.env('baseUrl_back'))
    cy.get('input[name="email"]').type(Cypress.env('email_gestor')) 
    cy.get('input[name="password"]').type(Cypress.env('contrasena')) 
    cy.get(':nth-child(2) > .btn').click() 
 
    
  })

  afterEach(() => {
    
    cy.wait(1000)
    //cy.get('body').screenshot({ overwrite: true, capture: 'fullPage' })
    cy.screenshot({ overwrite: true, capture: 'fullPage' });
    
   
  })

  //Cursos//
  
//Log añadir Recursos educativos//
it('1.Verificar resguardo de evento en Log Añadir Recursos educativos - Exitoso', () => {   
  // Rol Gestor
  
  cy.get('#main-menu > :nth-child(4) > :nth-child(1)').click()
  cy.get(':nth-child(4) > .sub-menu > ul > :nth-child(3)').click()
  cy.get('#btn-add-resource').click()
  cy.get('#title').type('Recurso educativo de prueba')
  cy.get('#description').type('Descripción corta 1 de prueba')
  // Selecciona la opción forma de recursao
  cy.get('#resource_way').select('Imagen')
  // Verifica que la opción seleccionada sea la correcta
  cy.get('#resource_way').should('have.value', 'IMAGE')
  cy.get('#educational_resource_type_uid').select('Aprendizaje')
  cy.get('#educational_resource_type_uid').should('have.value', '838752c5-6369-479b-9753-d177c44a3f1b')
  cy.get('button[type="submit"]#draft-button').click()
  cy.get('.toastify').should('contain', 'Recurso añadido correctamente')  
  cy.get('#main-menu > :nth-child(5) > :nth-child(1)').click()
  cy.get(':nth-child(5) > .sub-menu > ul > li').click()
  cy.get('.tabulator-col-title').contains('Fecha').click()
  cy.get('.tabulator-col-title').contains('Fecha').click()

});



})