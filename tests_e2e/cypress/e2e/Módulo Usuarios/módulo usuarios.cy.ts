// <reference types="cypress" />
Cypress.on('uncaught:exception', () => false),
describe('Módulo Usuarios', () => {
  beforeEach(() => {
   
    cy.visit(Cypress.env('baseUrl_back'))
    cy.get('input[name="email"]').type(Cypress.env('email_admin')) 
    cy.get('input[name="password"]').type(Cypress.env('contrasena')) 
    cy.get(':nth-child(2) > .btn').click() 
 
    
  })

  afterEach(() => {
    
    cy.wait(1000)
    //cy.get('body').screenshot({ overwrite: true, capture: 'fullPage' })
    cy.screenshot({ overwrite: true, capture: 'fullPage' });
    
   
  })

  it('1.Crear Usuarios - Exitoso', () => {   
    // Rol Administrador
    
    cy.get('#main-menu > :nth-child(3) > :nth-child(1)').click()
    cy.get(':nth-child(3) > .sub-menu > ul > li').click() 
    cy.get('#add-user-btn').click()
    cy.get('#first_name').type('Prueba')
    cy.get('#last_name').type('Usuario')
    cy.get('#nif').type('1234567P')    
    cy.get('#email').type('prueba@email.com')
    cy.get('#roles-ts-control').click()
    cy.contains('.option', 'Administrador').click()
    cy.get('body').click(0, 0)
    cy.get('#user-form button[type="submit"].btn.btn-primary').click() 
    //cy.get('.toastify').should('contain', 'Se ha creado el usuario correctamente')  

   
  });
 
  it('2.Crear Usuario - Fallido - Campos obligatorios vacios', () => {   
    // Rol Administrador
    //Campos vacios: nif y email
    
    cy.get('#main-menu > :nth-child(3) > :nth-child(1)').click()
    cy.get(':nth-child(3) > .sub-menu > ul > li').click() 
    cy.get('#add-user-btn').click()
    cy.get('#first_name').type('Prueba')
    cy.get('#last_name').type('Usuario')
    cy.get('#roles-ts-control').click()
    cy.contains('.option', 'Administrador').click()
    cy.get('body').click(0, 0)
    cy.get('#user-form button[type="submit"].btn.btn-primary').click() 
    cy.get('.toastify').should('contain', 'Algunos campos son incorrectos')
   
  });
  it('3.Editar usuarios - Exitoso', () => {   
    // Rol Administrador 
    // Edita nombre 
    
    cy.get('#main-menu > :nth-child(3) > :nth-child(1)').click()
    cy.get(':nth-child(3) > .sub-menu > ul > li').click() 
    cy.get('.input-with-button > .w-full').type('1234567P')
    cy.get('.search-table-btn').click()
    cy.get('.tabulator-row .tabulator-cell[tabulator-field="nif"]:contains("1234567P")')
      .closest('.tabulator-row')
      .find('.checkbox-cell input[type="checkbox"]')
      .check()
    cy.get('.tabulator-row .tabulator-cell[tabulator-field="nif"]:contains("1234567P")')
      .closest('.tabulator-row') 
      .find('button.action-btn') 
      .first() 
      .click()
    cy.get('#first_name').type('EditPrueba')
    cy.get('#user-form button[type="submit"].btn.btn-primary').click()
    cy.get('.toastify').should('contain', 'Se ha actualizado el usuario correctamente')  
   
  });
  it('4.Elimina usuario - Exitoso)', () => { 
    cy.get('#main-menu > :nth-child(3) > :nth-child(1)').click()
    cy.get(':nth-child(3) > .sub-menu > ul > li').click()
    cy.get('.input-with-button > .w-full').type('1234567P')
    cy.get('.search-table-btn').click()    
    cy.get('.tabulator-row .tabulator-cell[tabulator-field="nif"]:contains("1234567P")')
      .closest('.tabulator-row')
      .find('.checkbox-cell input[type="checkbox"]')
      .check()   
    cy.get('#delete-user-btn').click()
    cy.get('#confirmation-modal > .modal-body').should('be.visible')
    cy.get('#confirm-button').click()
    cy.get('.toastify').should('contain', 'Se han eliminado los usuarios correctamente')      

  })


})