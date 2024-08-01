// <reference types="cypress" />
Cypress.on('uncaught:exception', () => false),
describe('Autenticacion-Backend', () => {
  beforeEach(() => {
    cy.visit(Cypress.env('baseUrl_back')), {
      headers: {       
      },
      failOnStatusCode: false            
    };
  })

  afterEach(() => {
    cy.wait(1000)
    cy.get('body').screenshot({ overwrite: true })
  })

  it('1.Verificar rol Administrador autenticado', () => {  
    
    // Rol Administrador

    cy.get('input[name="email"]').type(Cypress.env('email_admin')) 
    cy.get('input[name="password"]').type(Cypress.env('contrasena')) 
    cy.get(':nth-child(2) > .btn').click()
    cy.get('header[id="poa-header"]').should('contain', 'Administrador')
    cy.get('#main-menu li span').should('contain', 'Administración')
    cy.get('#main-menu li span').should('contain', 'Catalogación')
    cy.get('#main-menu li span').should('contain', 'Usuarios')
    cy.get('#main-menu li span').should('contain', 'Log')
    cy.get('#main-menu li span').should('contain', 'Mi perfil')

  });

  it('2.Verificar rol Gestor autenticado', () => {  

    // Rol Gestor
    cy.get('input[name="email"]').type(Cypress.env('email_gestor')) 
    cy.get('input[name="password"]').type(Cypress.env('contrasena')) 
    cy.get(':nth-child(2) > .btn').click()
    cy.get('header[id="poa-header"]').should('contain', 'Gestor')
    cy.get('#main-menu li span').should('contain', 'Gestión')
    cy.get('#main-menu li span').should('contain', 'Catalogación')
    cy.get('#main-menu li span').should('contain', 'Notificaciones')
    cy.get('#main-menu li span').should('contain', 'Objetos de aprendizaje')
    cy.get('#main-menu li span').should('contain', 'Log')
    cy.get('#main-menu li span').should('contain', 'Credenciales')
    cy.get('#main-menu li span').should('contain', 'Analítica')
    cy.get('#main-menu li span').should('contain', 'Mi perfil')
  });

  it('3.Verificar rol Estudiante autenticado', () => {  

    // Rol Estudiante
    cy.get('input[name="email"]').type(Cypress.env('email_estudiante')) 
    cy.get('input[name="password"]').type(Cypress.env('contrasena')) 
    cy.get(':nth-child(2) > .btn').click()
    cy.get('header[id="poa-header"]').should('contain', 'Estudiante')
    cy.get('#main-menu li span').should('contain', 'Mi perfil')

  });

  it('4.Verificar rol Docente autenticado', () => {  

    // Rol Docente
    cy.get('input[name="email"]').type(Cypress.env('email_docente')) 
    cy.get('input[name="password"]').type(Cypress.env('contrasena')) 
    cy.get(':nth-child(2) > .btn').click()
    cy.get('header[id="poa-header"]').should('contain', 'Docente')
    cy.get('#main-menu li span').should('contain', 'Notificaciones')
    cy.get('#main-menu li span').should('contain', 'Objetos de aprendizaje')
    cy.get('#main-menu li span').should('contain', 'Credenciales')
    cy.get('#main-menu li span').should('contain', 'Mi perfil')

  });

  });

