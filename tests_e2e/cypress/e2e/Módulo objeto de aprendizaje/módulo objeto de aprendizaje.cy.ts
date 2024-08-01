// <reference types="cypress" />
Cypress.on('uncaught:exception', () => false),
describe('Módulo objeto de aprendizaje', () => {
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
  it('1.Añadir cursos - Exitoso', () => {   
    // Rol Gestor
    
    cy.get('#main-menu > :nth-child(4) > :nth-child(1)').click()
    cy.get(':nth-child(4) > .sub-menu > ul > :nth-child(1)').click() 
    cy.get('#add-course-btn').click()
    cy.get('#title').type('Pruebass3')
    cy.get('#description').type('Los contenidos del curso de prueba son los siguientes: Prueba 1 -Prueba 2')
    cy.get('#contact_information').type('Prueba de Información del contacto')
    // Selecciona la opción "Convocatoria prueba"
    cy.get('#call_uid').select('Convocatoria prueba')
    // Verifica que la opción seleccionada sea la correcta
    cy.get('#call_uid').should('have.value', 'f6a5efa2-d6a4-4c76-894b-3742161296a0')
    // Selecciona la opción Tipo de curso "MOOC2"
    cy.get('#course_type_uid').select('MOOC2');
    // Verifica que la opción seleccionada sea la correcta
    cy.get('#course_type_uid').should('have.value', '11a0cb08-e873-42ec-b420-f357e6353c98')
    cy.get('#educational_program_type_uid').select('Doctorado');
    // Verifica que la opción seleccionada sea la correcta
    cy.get('#educational_program_type_uid').should('have.value', '0044a4bc-1e88-4beb-9db4-838f6530faee')
    // Selecciona la opción Centro
    cy.get('#center_uid').select('Universidad de Murcia');
    // Verifica que la opción seleccionada sea la correcta
    cy.get('#center_uid').should('have.value', '739b90df-56fc-4fdd-9fc7-7c3344411278')
    cy.get('#inscription_start_date').click()
    // Ingresa la nueva fecha y hora 
    cy.get('#inscription_start_date').type('2024-07-30T21:45')
    cy.get('#inscription_finish_date').click()
    // Ingresa la nueva fecha y hora 
    cy.get('#inscription_finish_date').type('2024-08-05T21:45')
    cy.get('#realization_start_date').click()
    // Ingresa la nueva fecha y hora 
    cy.get('#realization_start_date').type('2024-07-30T21:45')
    cy.get('#realization_finish_date').click()
    // Ingresa la nueva fecha y hora 
    cy.get('#realization_finish_date').type('2024-08-05T21:45')
    //Selecciona tipo de calificación
    cy.get('#calification_type').select('Numérica');
    // Verifica que la opción seleccionada sea la correcta
    cy.get('#calification_type').should('have.value', 'NUMERICAL')
    cy.get('button[type="submit"]#draft-button').click()
    cy.get('.toastify').should('contain', 'Se ha añadido el curso correctamente')

   
  });

  it('2.Añadir cursos - Fallido - Campos obligatorios vacios', () => {   
    // Rol Gestor
    
    cy.get('#main-menu > :nth-child(4) > :nth-child(1)').click()
    cy.get(':nth-child(4) > .sub-menu > ul > :nth-child(1)').click() 
    cy.get('#add-course-btn').click()
    cy.get('#title').type('Prueba')
    cy.get('#description').type('Los contenidos del curso de prueba son los siguientes: Prueba 1 -Prueba 2')
    cy.get('#contact_information').type('Prueba de Información del contacto')
    // Selecciona la opción "Convocatoria prueba"
    cy.get('#call_uid').select('Convocatoria prueba')
    // Verifica que la opción seleccionada sea la correcta
    cy.get('#call_uid').should('have.value', 'f6a5efa2-d6a4-4c76-894b-3742161296a0')
    // Selecciona la opción Tipo de curso "MOOC2"
    cy.get('#course_type_uid').select('MOOC2');
    // Verifica que la opción seleccionada sea la correcta
    cy.get('#course_type_uid').should('have.value', '11a0cb08-e873-42ec-b420-f357e6353c98')
    cy.get('#educational_program_type_uid').select('Ninguno');
    // Verifica que la opción seleccionada sea la correcta
    cy.get('#educational_program_type_uid').should('have.value', '')
    
    // Selecciona la opción Centro
    cy.get('#center_uid').select('Universidad de Murcia');
    // Verifica que la opción seleccionada sea la correcta
    cy.get('#center_uid').should('have.value', '739b90df-56fc-4fdd-9fc7-7c3344411278')
    cy.get('#inscription_start_date').click()
    // Ingresa la nueva fecha y hora 
    cy.get('#inscription_start_date').type('2024-07-30T21:45')
    cy.get('#inscription_finish_date').click()
    // Ingresa la nueva fecha y hora 
    cy.get('#inscription_finish_date').type('2024-08-05T21:45')  
    cy.get('#realization_start_date').click()
    // Ingresa la nueva fecha y hora 
    cy.get('#realization_start_date').type('2024-07-30T21:45')
    cy.get('#realization_finish_date').click()
    // Ingresa la nueva fecha y hora 
    cy.get('#realization_finish_date').type('2024-08-05T21:45')
    //Selecciona tipo de calificación
    cy.get('#calification_type').select('Numérica');
    // Verifica que la opción seleccionada sea la correcta
    cy.get('#calification_type').should('have.value', 'NUMERICAL')
    cy.get('button[type="submit"]#draft-button').click()
    cy.get('.toastify').should('contain', 'Algunos campos son incorrectos')  

   
  });

  it('3.Editar cursos - Exitoso', () => {   
    // Rol Gestor
    
    cy.get('#main-menu > :nth-child(4) > :nth-child(1)').click()
    cy.get(':nth-child(4) > .sub-menu > ul > :nth-child(1)').click() 
    cy.get('.poa-container > .table-control-header > .input-with-button > .w-full').type('Pruebass3')
    cy.get('.poa-container > .table-control-header > .input-with-button > .search-table-btn > svg').click()
    cy.get('.tabulator-row .tabulator-cell[tabulator-field="title"]:contains("Pruebass3")')
      .closest('.tabulator-row')
      .find('.checkbox-cell input[type="checkbox"]')
      .check()
    cy.get('.tabulator-row .tabulator-cell[tabulator-field="title"]:contains("Pruebass3")')
      .closest('.tabulator-row') 
      .find('button.action-btn') 
      .first() 
      .click()
    cy.get('#contact_information').clear()
    cy.get('#contact_information').type('Prueba de Información del contacto editado')
    cy.get('#call_uid').select('Selecciona una convocatoria')
    cy.get('#call_uid').should('have.value', '')
    cy.get('#call_uid').select('Convocatoria prueba')
    // Verifica que la opción seleccionada sea la correcta
    cy.get('#call_uid').should('have.value', 'f6a5efa2-d6a4-4c76-894b-3742161296a0')
    cy.get('button[type="submit"]#draft-button').click()
    cy.get('.toastify').should('contain', 'Se ha actualizado el curso correctamente')   
  });
 
  it('4.Verificar botón Ver listado de alumnos )', () => { 
    cy.get('#main-menu > :nth-child(4) > :nth-child(1)').click()
    cy.get(':nth-child(4) > .sub-menu > ul > :nth-child(1)').click() 
    cy.get('.poa-container > .table-control-header > .input-with-button > .w-full').type('Pruebass1')
    cy.get('.poa-container > .table-control-header > .input-with-button > .search-table-btn > svg').click()
    cy.get('.tabulator-row .tabulator-cell[tabulator-field="title"]:contains("Pruebass1")')
      .closest('.tabulator-row')
      .find('.checkbox-cell input[type="checkbox"]')
      .check()
      cy.get('.tabulator-row > :nth-child(37)').click()
      cy.get('button[title="Listado de alumnos del curso"]').click()       

  })

  it('5.Verificar botón Crear nueva edición a partir de un curso creado)', () => { 
    cy.get('#main-menu > :nth-child(4) > :nth-child(1)').click()
    cy.get(':nth-child(4) > .sub-menu > ul > :nth-child(1)').click() 
    cy.get('.poa-container > .table-control-header > .input-with-button > .w-full').type('Curso de prueba1')
    cy.get('.poa-container > .table-control-header > .input-with-button > .search-table-btn > svg').click()
    cy.get('.tabulator-row .tabulator-cell[tabulator-field="title"]:contains("Curso de prueba1")')
      .closest('.tabulator-row')
      .find('.checkbox-cell input[type="checkbox"]')
      .check()
      cy.get('.tabulator-row > :nth-child(37)').click()
      cy.get('button[title="Crear nueva edición a partir de este curso"]').click()       

  })

  it('6.Verificar botón Envío de credenciales)', () => { 
    cy.get('#main-menu > :nth-child(4) > :nth-child(1)').click()
    cy.get(':nth-child(4) > .sub-menu > ul > :nth-child(1)').click() 
    cy.get('.poa-container > .table-control-header > .input-with-button > .w-full').type('Pruebass1')
    cy.get('.poa-container > .table-control-header > .input-with-button > .search-table-btn > svg').click()
    cy.get('.tabulator-row .tabulator-cell[tabulator-field="title"]:contains("Pruebass1")')
      .closest('.tabulator-row')
      .find('.checkbox-cell input[type="checkbox"]')
      .check()
      cy.get('.tabulator-row > :nth-child(37)').click()
      cy.get('button[title="Envío de credenciales"]').click()       

  })

  it('7. Verificar botón selector de columna', () => { 
    cy.get('#main-menu > :nth-child(4) > :nth-child(1)').click()
    cy.get(':nth-child(4) > .sub-menu > ul > :nth-child(1)').click() 
    cy.get('.tabulator-col-content .columns-selector').click()   

  })
  
 // Programas Formativos //

 it('8.Añadir Programa Formativo Error en fecha de formación', () => {   
  // Rol Gestor
  
  cy.get('#main-menu > :nth-child(4) > :nth-child(1)').click()
  cy.get(':nth-child(4) > .sub-menu > ul > :nth-child(2)').click() 
  cy.get('#new-educational-program-btn').click()
  cy.get('#name').type('Programa formativo de prueba')
  cy.get('#description').type('Descripción corta 1 de prueba')
  // Selecciona la opción tipo de programa
  cy.get('#educational_program_type_uid').select('Doctorado')
  // Verifica que la opción seleccionada sea la correcta
  cy.get('#educational_program_type_uid').should('have.value', '0044a4bc-1e88-4beb-9db4-838f6530faee')
  // Selecciona la opción "Convocatoria prueba"
  cy.get('#call_uid').select('Convocatoria prueba')
  cy.get('#inscription_start_date').click()
  // Ingresa la nueva fecha y hora 
  cy.get('#inscription_start_date').type('2024-10-01T21:45')
  cy.get('#inscription_finish_date').click()
  // Ingresa la nueva fecha y hora 
  cy.get('#inscription_finish_date').type('2024-10-01T21:45')
  cy.get('#realization_start_date').click()
  // Ingresa la nueva fecha y hora 
  cy.get('#realization_start_date').type('2024-10-02T21:45')
  cy.get('#realization_finish_date').click()
  // Ingresa la nueva fecha y hora 
  cy.get('#realization_finish_date').type('2024-10-03T21:45')
  cy.get('#courses-ts-control').click()
  cy.get('#courses-ts-control').type('Curso de PMP dirección de proyectos (copia)')
  cy.get('.ts-dropdown-content .option')
      .contains('Curso de PMP dirección de proyectos (copia)')
      .click()
  cy.get('#courses').find('option:selected').should('have.length', 1)
  //cy.get('#courses-ts-control').click()
  cy.get('button[type="submit"]#draft-button').click()
  cy.get('.toastify').should('contain', 'Algunos cursos no están entre las fechas de realización del programa formativo')

 
});
 it('9.Añadir Programa Formativo - fallido - Campos obligatorios', () => {   
  // Rol Gestor
  
  cy.get('#main-menu > :nth-child(4) > :nth-child(1)').click()
  cy.get(':nth-child(4) > .sub-menu > ul > :nth-child(2)').click() 
  cy.get('#new-educational-program-btn').click()
  cy.get('#name').type('Programa formativo de prueba')
  cy.get('#description').type('Descripción corta 1 de prueba')
  // Selecciona la opción tipo de programa
  cy.get('#educational_program_type_uid').select('Doctorado')
  // Verifica que la opción seleccionada sea la correcta
  cy.get('#educational_program_type_uid').should('have.value', '0044a4bc-1e88-4beb-9db4-838f6530faee')
  cy.get('#inscription_start_date').click()
  // Ingresa la nueva fecha y hora 
  cy.get('#inscription_start_date').type('2024-07-30T21:45')
  cy.get('#inscription_finish_date').click()
  // Ingresa la nueva fecha y hora 
  cy.get('#inscription_finish_date').type('2024-08-05T21:45')
  cy.get('#realization_start_date').click()
  // Ingresa la nueva fecha y hora 
  cy.get('#realization_start_date').type('2024-07-30T21:45')
  cy.get('#realization_finish_date').click()
  // Ingresa la nueva fecha y hora 
  cy.get('#realization_finish_date').type('2024-08-05T21:45')
  cy.get('button[type="submit"]#draft-button').click()
  cy.get('.toastify').should('contain', 'Algunos campos son incorrectos')

 
});

//Recursos educativos//
it('10.Añadir Recursos educativos - Exitoso', () => {   
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
});

it('11.Añadir Recursos educativos - fallido - Campos obligatorios', () => {   
  // Rol Gestor
  
  cy.get('#main-menu > :nth-child(4) > :nth-child(1)').click()
  cy.get(':nth-child(4) > .sub-menu > ul > :nth-child(3)').click()
  cy.get('#btn-add-resource').click()
  cy.get('#description').type('Descripción corta 1 de prueba')
  // Selecciona la opción forma de recursao
  cy.get('#resource_way').select('Imagen')
  // Verifica que la opción seleccionada sea la correcta
  cy.get('#resource_way').should('have.value', 'IMAGE')
  cy.get('#educational_resource_type_uid').select('Aprendizaje')
  cy.get('#educational_resource_type_uid').should('have.value', '838752c5-6369-479b-9753-d177c44a3f1b')
  cy.get('button[type="submit"]#draft-button').click()
  cy.get('.toastify').should('contain', 'Algunos campos son incorrectos') 
});

it('12.Editar Recursos educativos - Exitoso', () => {   
  // Rol Gestor
cy.get('#main-menu > :nth-child(4) > :nth-child(1)').click()
cy.get(':nth-child(4) > .sub-menu > ul > :nth-child(3)').click()
cy.get('.poa-container > .table-control-header > .input-with-button > .w-full').type('Recurso educativo de prueba')
cy.get('.poa-container > .table-control-header > .input-with-button > .search-table-btn > svg').click()
cy.get('.tabulator-row .tabulator-cell[tabulator-field="title"]:contains("Recurso educativo de prueba")')
  .closest('.tabulator-row')
  .find('.checkbox-cell input[type="checkbox"]')
  .check()
cy.get('.tabulator-row .tabulator-cell[tabulator-field="title"]:contains("Recurso educativo de prueba")')
  .closest('.tabulator-row') 
  .find('button.action-btn') 
  .first() 
  .click()
cy.get('#description').clear()
cy.get('#description').type('Descripción editada del recurso educativo')
cy.get('button[type="submit"]#draft-button').click()
cy.get('.toastify').should('contain', 'Recurso añadido correctamente')
})

it('13.Elimina Recurso educativo - Exitoso)', () => { 
  cy.get('#main-menu > :nth-child(4) > :nth-child(1)').click()
  cy.get(':nth-child(4) > .sub-menu > ul > :nth-child(3)').click()
  cy.get('.poa-container > .table-control-header > .input-with-button > .w-full').type('Recurso educativo de prueba')
  cy.get('.poa-container > .table-control-header > .input-with-button > .search-table-btn > svg').click()
  cy.get('.tabulator-row .tabulator-cell[tabulator-field="title"]:contains("Recurso educativo de prueba")')
    .closest('.tabulator-row')
    .find('.checkbox-cell input[type="checkbox"]')
    .check()
    cy.get('#btn-delete-resources').click()
    cy.get('#confirmation-modal > .modal-body').should('be.visible')
    cy.get('#confirm-button').click()
    //cy.get('.toastify').should('contain', 'Recurso eliminado correctamente')      

})

it('14. Verificar Recursos educativos por usuarios', () => {   
  // Rol Gestor
  
  cy.get('#main-menu > :nth-child(4) > :nth-child(1)').click()
  cy.get(':nth-child(4) > .sub-menu > ul > :nth-child(4)').click()
  cy.get('.poa-container > .table-control-header > .input-with-button > .w-full').type('Ana')
  cy.get('.poa-container > .table-control-header > .input-with-button > .search-table-btn > svg').click()
  cy.get('.tabulator-row-odd > .text-center').click()
  cy.get('#educational-resources-per-user-modal').should('be.visible');
  // Verifica que el título de la modal contenga el nombre "Ana"
  cy.get('.modal-title').should('contain', 'Ana');
});

})