// <reference types="cypress" />
Cypress.on('uncaught:exception', () => false),
describe('Autenticacion y registro Frontend', () => {
  beforeEach(() => {
    cy.visit('/'), {
      headers: {       
      },
      failOnStatusCode: false            
    };
  })

  afterEach(() => {
    cy.wait(1000)
    cy.get('body').screenshot({ overwrite: true })
  })

  it('1.Login Correcto - Estudiante', () => {  

    // Enlace de la sección "login"
    cy.get('.border-color_1').click()
    cy.get('input[name="email"]').type(Cypress.env('email_estudiante')) 
    cy.get('input[name="password"]').type(Cypress.env('contrasena')) 
    cy.get('#loginFormDesktop button.bg-color_1').click()
    cy.url().should('include', Cypress.env('baseUrl'))

  });

  it('2.Login Incorrecto', () => {
   
      // Enlace de la sección "login"
      cy.get('.border-color_1').click()
      cy.get('input[name="email"]').type(Cypress.env('email_estudiante')) 
      cy.get('input[name="password"]').type(Cypress.env('contrasenaincorrecta')) 
      cy.get('#loginFormDesktop button.bg-color_1').click()
      cy.get('.toastify').should('be.visible')
      // Verificar el texto de la alerta toastify on error
      cy.get('.toastify').should('contain', 'No se ha encontrado ninguna cuenta con esas credenciales')

    
  });

  it('3.Correo vacío', () => {
    
     // Enlace de la sección "login"
     cy.get('.border-color_1').click()
     cy.get('input[name="email"]').should('be.empty')
     cy.get('input[name="password"]').type(Cypress.env('contrasena')) 
     cy.get('#loginFormDesktop button.bg-color_1').click()
     cy.get('.toastify').should('be.visible')
      // Verificar el texto de la alerta toastify on error
     cy.get('.toastify').should('contain', 'No se ha encontrado ninguna cuenta con esas credenciales')

  });

  it('4.Contraseña vacía', () => {   

       // Enlace de la sección "login"
       cy.get('.border-color_1').click()
       cy.get('input[name="email"]').type(Cypress.env('email_estudiante')) 
       cy.get('input[name="password"]').should('be.empty')
       cy.get('#loginFormDesktop button.bg-color_1').click()
       cy.get('.toastify').should('be.visible')
        // Verificar el texto de la alerta toastify on error
       cy.get('.toastify').should('contain', 'No se ha encontrado ninguna cuenta con esas credenciales')
  });

  it('5.Olvidé la contraseña', () => {
    
   
    cy.get('a[href="/user/password"]').click()

  });

  //it('6.Registro', () => { 
        
    

  //});

  });

