// <reference types="cypress" />
Cypress.on('uncaught:exception', () => false),
describe('Módulo Gestión', () => {
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

  it('1.Crear convocatoria - Exitoso', () => {   
    // Rol Gestor
    
    cy.get('#main-menu > :nth-child(1) > :nth-child(1) > span').click()
    cy.get(':nth-child(1) > .sub-menu > ul > :nth-child(1) > a').click() 
    cy.get('#new-call-btn').click()
    cy.get('#name').type('Convocatoria prueba19')
    cy.get('#description').type('Descripcion corta 1')
    cy.get('#start_date').click();
    // Ingresa la fecha y hora en el formato correcto
    cy.get('#start_date').type('2024-07-30T21:45');
    cy.get('#end_date').click();
    // Ingresa la fecha y hora en el formato correcto
    cy.get('#end_date').type('2024-07-30T23:45');
    cy.get('#program_types-ts-control').click()
    // Selecciona la opción "DoctoradoDoctorado"
    cy.get('#program_types-opt-1').click()
    cy.get('#program_types-ts-control').click()
    cy.get('#call-form button[type="submit"].btn.btn-primary').click()
    //cy.get('.toastify').should('contain', 'Convocatoria añadida correctamente')  

   
  });
 
  it('2.Crear convocatoria - Fallido - Error Fecha', () => {   
    // Rol Gestor
    
    cy.get('#main-menu > :nth-child(1) > :nth-child(1) > span').click()
    cy.get(':nth-child(1) > .sub-menu > ul > :nth-child(1) > a').click() 
    cy.get('#new-call-btn').click()
    cy.get('#name').type('Convocatoria prueba19')
    cy.get('#description').type('Descripcion corta 1')
    // cy.get('#start_date').click();
    // // Ingresa la fecha y hora en el formato correcto
    // cy.get('#start_date').type('2024-07-30T09:45');
    cy.get('#end_date').click();
    // Ingresa la fecha y hora en el formato correcto
    cy.get('#end_date').type('2024-07-30T10:45');
    cy.get('#program_types-ts-control').click()
    // Selecciona la opción "DoctoradoDoctorado"
    cy.get('#program_types-opt-1').click()
    cy.get('#program_types-ts-control').click()
    cy.get('#call-form button[type="submit"].btn.btn-primary').click()
    cy.get('.toastify').should('contain', 'Algunos campos son incorrectos')
   
  });
  it('3.Editar convocatoria - Exitoso', () => {   
    // Rol Gestor 
    // Edita Descripción, fecha inicio y selecciona opción Grado 
    
    cy.get('#main-menu > :nth-child(1) > :nth-child(1) > span').click()
    cy.get(':nth-child(1) > .sub-menu > ul > :nth-child(1) > a').click() 
    cy.contains('.tabulator-row', 'Convocatoria prueba19')
            .within(() => {
                cy.get('.btn.action-btn').click();
            })
    cy.get('#description').clear().type('Descripcion larga de prueba 1')   
    cy.get('#end_date').click()
    // Ingresa la nueva fecha y hora 
    cy.get('#end_date').type('2024-07-30T21:45')
    cy.get('#program_types-ts-control').click()
    // Selecciona la opción Grado
    cy.get('#program_types-opt-2').click()    
    cy.get('#program_types-ts-control').click()
    cy.get('#call-form button[type="submit"].btn.btn-primary').click()
    cy.get('.toastify').should('contain', 'Convocatoria actualizada correctamente')  
   
  });
  it('4.Elimina Convocatoria - Exitoso)', () => { 
    cy.get('#main-menu > :nth-child(1) > :nth-child(1) > span').click()
    cy.get(':nth-child(1) > .sub-menu > ul > :nth-child(1) > a').click() 
    cy.get('.tabulator-row .tabulator-cell[tabulator-field="name"]:contains("Convocatoria prueba19")')
      .closest('.tabulator-row')
      .find('.checkbox-cell input[type="checkbox"]')
      .check()
      cy.get('#btn-edit-call').click()
      cy.get('#confirmation-modal > .modal-body').should('be.visible')
      cy.get('#confirm-button').click()
      cy.get('.toastify').should('contain', 'Convocatoria eliminada correctamente')      

  })

  it('5.Envío de notificaciones - Generales - Validación de campos vacios)', () => { 
    cy.get('#main-menu > :nth-child(3) > :nth-child(1)').click()
    cy.get(':nth-child(3) > .sub-menu > ul > :nth-child(1)').click()
    cy.get('#add-notification-general-btn').click()
    cy.get('#type').select('Usuarios concretos')
    cy.get('#notification_type_uid').select('Informaciones')
    //cy.get('#users-ts-control').type('Pedro Pérez')
    cy.get('#users-ts-control').type('Pedro')
    // Esperar que las opciones del dropdown estén visibles
    cy.get('#users-ts-dropdown').should('be.visible')
    // Seleccionar la opción "Pedro Administrador"
    cy.get('#users-opt-2').click()
    //cy.get('#title').type('informaciones de prueba')
    //cy.get('#description').type('Descripción de prueba')
    cy.get('#start_date').click();
    // Ingresa la fecha y hora en el formato correcto
    cy.get('#start_date').type('2024-07-30T18:45')
    cy.get('#end_date').click();
    // Ingresa la fecha y hora en el formato correcto
    cy.get('#end_date').type('2024-07-30T19:00')
    cy.get('#notification-general-form button[type="submit"].btn.btn-primary').click()    
    cy.get('.toastify').should('contain', 'Ha ocurrido un error')

  })
  
  it('6.Envío de notificaciones - Generales - Nueva notificación Exitoso)', () => { 
    cy.get('#main-menu > :nth-child(3) > :nth-child(1)').click()
    cy.get(':nth-child(3) > .sub-menu > ul > :nth-child(1)').click()
    cy.get('#add-notification-general-btn').click()
    cy.get('#type').select('Usuarios concretos')
    cy.get('#notification_type_uid').select('Informaciones')
    //cy.get('#users-ts-control').type('Pedro Pérez')
    cy.get('#users-ts-control').type('Pedro')
    // Esperar que las opciones del dropdown estén visibles
    cy.get('#users-ts-dropdown').should('be.visible')
    // Seleccionar la opción "Pedro Administrador"
    cy.get('#users-opt-2').click()
    cy.get('#title').type('informaciones de prueba')
    cy.get('#description').type('Descripción de prueba')
    cy.get('#start_date').click();
    // Ingresa la fecha y hora en el formato correcto
    cy.get('#start_date').type('2024-07-30T18:45')
    cy.get('#end_date').click();
    // Ingresa la fecha y hora en el formato correcto
    cy.get('#end_date').type('2024-07-30T19:00')
    cy.get('#notification-general-form button[type="submit"].btn.btn-primary').click()    
    cy.get('.toastify').should('contain', 'Notificación general añadida correctamente')

    
  })
 
  it('7.Editar Notificaciones - Generales - Exitoso', () => {   
    // Rol Gestor 
    //Edita el campo Descripción    
    cy.get('#main-menu > :nth-child(3) > :nth-child(1)').click()
    cy.get(':nth-child(3) > .sub-menu > ul > :nth-child(1)').click()
    cy.get('.tabulator-row .tabulator-cell[tabulator-field="title"]:contains("informaciones de prueba")')
      .closest('.tabulator-row')
      .find('.checkbox-cell input[type="checkbox"]')
      .check()
    cy.get('.tabulator-row .tabulator-cell[tabulator-field="title"]:contains("informaciones de prueba")')
      .closest('.tabulator-row') 
      .find('button.action-btn') 
      .first() 
      .click()
  //Borrar contenido del campo 
  cy.get('#description').clear()
  cy.get('#description').type('Descripción editada de prueba')  
  cy.get('#notification-general-form button[type="submit"].btn.btn-primary').click()    
  cy.get('.toastify').should('contain', 'Notificación general actualizada correctamente') 
   
  });

  it('8.Visualizar usuarios que han visto la Notificación - Generales - Exitoso', () => {   
    // Rol Gestor 
    //Visualizar notificación de prueba    
    cy.get('#main-menu > :nth-child(3) > :nth-child(1)').click()
    cy.get(':nth-child(3) > .sub-menu > ul > :nth-child(1)').click()
    cy.get('.tabulator-row .tabulator-cell[tabulator-field="title"]:contains("informaciones de prueba")')
      .closest('.tabulator-row')
      .find('.checkbox-cell input[type="checkbox"]')
      .check()
    cy.get('.tabulator-row .tabulator-cell[tabulator-field="title"]:contains("informaciones de prueba")')
      .closest('.tabulator-row') 
      .find('button.action-btn') 
      .eq(1) 
      .click()
     
  });

  it('9.Eliminar Notificación - Generales - Exitoso', () => {   
    // Rol Gestor 
    //Visualizar notificación de prueba    
    cy.get('#main-menu > :nth-child(3) > :nth-child(1)').click()
    cy.get(':nth-child(3) > .sub-menu > ul > :nth-child(1)').click()
    cy.get('.tabulator-row .tabulator-cell[tabulator-field="title"]:contains("informaciones de prueba")')
      .closest('.tabulator-row')
      .find('.checkbox-cell input[type="checkbox"]')
      .check()
    cy.get('#delete-notification-general-btn').click()
    cy.get('#confirmation-modal > .modal-body').should('be.visible')
    cy.get('#confirm-button').click()
    cy.get('.toastify').should('contain', 'Notificaciones eliminadas correctamente')  
     
  });

  it('10.Envío de notificaciones - Por Correo - Nueva notificación Exitoso', () => { 
    cy.get('#main-menu > :nth-child(3) > :nth-child(1)').click()
    cy.get(':nth-child(3) > .sub-menu > ul > :nth-child(2) > a').click()
    cy.get('#add-notification-email-btn').click()
    cy.get('#type').select('Usuarios concretos')
    cy.get('#notification_type_uid').select('Informaciones')
    cy.get('#send_date').click();
    // Ingresa la fecha y hora en el formato correcto
    cy.get('#send_date').type('2024-07-30T18:45')    
    cy.get('#users-ts-control').type('Pedro')
    // Esperar que las opciones del dropdown estén visibles
    cy.get('#users-ts-dropdown').should('be.visible')
    // Seleccionar la opción "Pedro Administrador"
    cy.get('#users-opt-1').click()
    cy.get('#subject').type('informaciones de prueba')
    cy.get('#body').type('Descripción o cuerpo del correo de prueba')    
    cy.get('#email-notification-modal-add-btns > .btn-primary').click()  
  });

  it('11.Envío de notificaciones - Por correo - Validación de campos vacios', () => { 
    cy.get('#main-menu > :nth-child(3) > :nth-child(1)').click()
    cy.get(':nth-child(3) > .sub-menu > ul > :nth-child(2) > a').click()
    cy.get('#add-notification-email-btn').click()
    cy.get('#type').select('Usuarios concretos')
    cy.get('#notification_type_uid').select('Informaciones')
    cy.get('#send_date').click()
    cy.get('#send_date').type('2024-07-30T18:45')    
    cy.get('#users-ts-control').type('Pedro')
    cy.get('#users-ts-dropdown').should('be.visible')
    // Seleccionar la opción "Pedro Administrador"
    cy.get('#users-opt-1').click()
    //cy.get('#subject').type('informaciones de prueba')
    cy.get('#body').type('Descripción o cuerpo del correo de prueba')    
    cy.get('#email-notification-modal-add-btns > .btn-primary').click()  

  })



  it('12.Verificar funcionamiento opción cambio de estados ', () => {   
    // Rol Gestor 
     
    cy.get(':nth-child(4) > :nth-child(1) > span').click()
    cy.get(':nth-child(4) > .sub-menu > ul > :nth-child(1) > a').click()
    cy.get('.tabulator-row .tabulator-cell[tabulator-field="title"]:contains("Emprender: de la idea a la acción (nueva edición)")')
      .closest('.tabulator-row')
      .find('.checkbox-cell input[type="checkbox"]')
      .check()
    cy.get('#change-statuses-btn').click()
    //Cambio de estado
    cy.get('.course-name').contains('Emprender: de la idea a la acción (nueva edición)')
    .closest('.change-status-course')
    .find('.status-course') 
    .select('PENDING_PUBLICATION')
     
  })
//   //PENDIENTE
//   it('13.Aprobación de Cursos - Exitoso', () => {   
//     // Rol Gestor 
//     //Aprobación de Cursos 
//     cy.get(':nth-child(4) > :nth-child(1) > span').click()
//     cy.get(':nth-child(4) > .sub-menu > ul > :nth-child(1) > a').click()
//     cy.get('.tabulator-row .tabulator-cell[tabulator-field="title"]:contains("Emprender: de la idea a la acción (nueva edición)")')
//       .closest('.tabulator-row')
//       .find('.checkbox-cell input[type="checkbox"]')
//       .check()
//     cy.get('#change-statuses-btn').click()
//     //Cambio de estado
//     cy.get('.course-name').contains('Emprender: de la idea a la acción (nueva edición)')
//     .closest('.change-status-course')
//     .find('.status-course') 
//     .select('ACCEPTED')
//     cy.get(':nth-child(1) > .course > div > .reason-status-course').type('Motivo de prueba de aprobación del curso.')
//     cy.get('#confirm-change-statuses-btn').click()

    
     
//   })
// //PENDIENTE
//   it('14.Emisión de credenciales Estudiantes- Exitoso', () => {   
//     // Rol Gestor 
    
//     cy.get('#main-menu > :nth-child(6) > :nth-child(1)').click()
//     cy.get(':nth-child(6) > .sub-menu > ul > :nth-child(1)').click()
//     cy.get('.tabulator-table > :nth-child(5) > .text-center').click()
//     cy.get('.tabulator-row > [tabulator-field="pivot.credential"]').click()
//   })
// //PENDIENTE
//   it('15.Emisión de credenciales Profesor- Exitoso', () => {   
//     // Rol Gestor 
  
//     cy.get('#main-menu > :nth-child(6) > :nth-child(1)').click()
//     cy.get(':nth-child(6) > .sub-menu > ul > :nth-child(1)').click()
//     cy.get('.tabulator-table > :nth-child(5) > .text-center').click()
//     cy.get('.tabulator-row > [tabulator-field="pivot.credential"]').click()
//   })



})