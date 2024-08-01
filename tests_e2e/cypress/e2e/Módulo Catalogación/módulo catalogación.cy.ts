// <reference types="cypress" />
Cypress.on('uncaught:exception', () => false),
describe('Módulo Catalogación', () => {
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
// Pruebas Categorías//
  it('1.Crear categorías - Exitoso', () => {   
        
    cy.get('#main-menu > :nth-child(2) > :nth-child(1)').click()
    cy.get(':nth-child(2) > .sub-menu > ul > :nth-child(1) > a').click()
    cy.get('#new-category-btn').click()
    cy.wait(1000)
    cy.get('#name').type('Prueba categoría')
    cy.get('#description').type('Descripcion corta 1')
    const fileName = 'prueba.jpg'; 
    // Simula la carga de un archivo
    cy.get('input[type="file"]').attachFile(fileName);
    // Verifica que el nombre del archivo se muestre correctamente
    cy.get('#image-name').should('contain', fileName);
    cy.get('#color').click()
    cy.get('#clr-color-value').type('#fff')   
    cy.get('#category-form button[type="submit"].btn.btn-primary').click()
    cy.get('.toastify').should('contain', 'Categoría añadida correctamente')  

   
  });
 
  it('2.Crear categoria - Fallido - Error imagen', () => {   
       
    cy.get('#main-menu > :nth-child(2) > :nth-child(1)').click()
    cy.get(':nth-child(2) > .sub-menu > ul > :nth-child(1) > a').click()
    cy.get('#new-category-btn').click()
    cy.get('#name').type('Prueba categoría')
    cy.get('#description').type('Descripcion corta 1')
    cy.get('.select-file-container > .btn').attachFile('prueba.jpg')
    // Verificar que el contenedor de la imagen se muestre
    cy.get('#image_path_preview').should('be.visible')
    cy.get('#color').click()
    cy.get('#clr-color-value').type('#fff')   
    cy.get('#category-form button[type="submit"].btn.btn-primary').click()
    cy.get('.toastify').should('contain', 'Algunos campos son incorrectos')
   
  });
  it('3.Editar categoría - Exitoso', () => {       
    // Edita Descripción     
    cy.get('#main-menu > :nth-child(2) > :nth-child(1)').click()
    cy.get(':nth-child(2) > .sub-menu > ul > :nth-child(1) > a').click()
    cy.contains('label', 'Prueba categoría') 
      .closest('.flex') 
      .find('input.element-checkbox') 
      .check() 
      .should('be.checked')
    cy.contains('label', 'Prueba categoría') 
      .closest('.flex') 
      .find('button.edit-btn') 
      .click() 
      .should('have.class', 'edit-btn')
    cy.get('#description').clear().type('Descripcion larga de prueba 1')   
    cy.get('#category-form button[type="submit"].btn.btn-primary').click()
    cy.get('.toastify').should('contain', 'Categoría modificada correctamente') 
    
   
  });
  it('4.Elimina Categoría - Exitoso)', () => { 
    cy.get('#main-menu > :nth-child(2) > :nth-child(1)').click()
    cy.get(':nth-child(2) > .sub-menu > ul > :nth-child(1) > a').click()
    cy.contains('label', 'Prueba categoría') 
      .closest('.flex') 
      .find('input.element-checkbox') 
      .check() 
      .should('be.checked')
      cy.get('#btn-delete-categories').click()
      cy.get('#confirmation-modal > .modal-body').should('be.visible')
      cy.get('#confirm-button').click()
      cy.get('.toastify').should('contain', 'Categorías eliminadas correctamente')      

  })

  // Pruebas Tipos de Curso//
  it('5.Crear tipos de curso - Exitoso', () => {   
        
    cy.get('#main-menu > :nth-child(2) > :nth-child(1)').click()
    cy.get(':nth-child(2) > .sub-menu > ul > :nth-child(2)').click()
    cy.get('#add-course-type-btn').click()
    cy.wait(1000)
    cy.get('#name').type('Prueba Tipo de curso')
    cy.get('#description').type('Descripcion corta 1')
    cy.get('#course-type-form button[type="submit"].btn.btn-primary').click()
    cy.get('.toastify').should('contain', 'Tipo de curso añadido correctamente')  

   
  });

  it('6.Crear tipo de cursos - fallido', () => {   
        
    cy.get('#main-menu > :nth-child(2) > :nth-child(1)').click()
    cy.get(':nth-child(2) > .sub-menu > ul > :nth-child(2)').click()
    cy.get('#add-course-type-btn').click()
    cy.wait(1000)
    cy.get('#name').clear()
    cy.get('#description').type('Descripcion corta 1')
    cy.get('#course-type-form button[type="submit"].btn.btn-primary').click()
    cy.get('.toastify').should('contain', 'Algunos campos son incorrectos')  

   
  });

  it('7.Editar Tipo de cursos - Exitoso', () => {       
    // Edita Descripción     
    cy.get('#main-menu > :nth-child(2) > :nth-child(1)').click()
    cy.get(':nth-child(2) > .sub-menu > ul > :nth-child(2)').click()
    cy.get('.tabulator-row .tabulator-cell[tabulator-field="name"]:contains("Prueba Tipo de curso")')
      .closest('.tabulator-row')
      .find('.checkbox-cell input[type="checkbox"]')
      .check()
    cy.get('.tabulator-row .tabulator-cell[tabulator-field="name"]')
      .contains('Prueba Tipo de curso') 
      .closest('.tabulator-row') 
      .find('button.btn.action-btn') 
      .click()
    cy.get('#description').clear().type('Descripcion larga de prueba 1')   
    cy.get('#course-type-form button[type="submit"].btn.btn-primary').click()
    cy.get('.toastify').should('contain', 'Tipo de curso actualizado correctamente') 
 
  })

  it('8.Elimina Tipo de Curso - Exitoso)', () => { 
    cy.get('#main-menu > :nth-child(2) > :nth-child(1)').click()
    cy.get(':nth-child(2) > .sub-menu > ul > :nth-child(2)').click()
    cy.get('.tabulator-row .tabulator-cell[tabulator-field="name"]:contains("Prueba Tipo de curso")')
      .closest('.tabulator-row')
      .find('.checkbox-cell input[type="checkbox"]')
      .check()
    cy.get('#delete-course-type-btn').click()
    cy.get('#confirmation-modal > .modal-body').should('be.visible')
    cy.get('#confirm-button').click()
    cy.get('.toastify').should('contain', 'Tipos de curso eliminados correctamente')      

  })

  //Pruebas de Tipos de recusros educativos //
  it('9.Crear Tipos de recursos educativos - Exitoso', () => {   
        
    cy.get('#main-menu > :nth-child(2) > :nth-child(1)').click()
    cy.get(':nth-child(2) > .sub-menu > ul > :nth-child(3)').click()
    cy.get('#add-educational-resource-type-btn').click()
    cy.wait(1000)
    cy.get('#name').type('Prueba Tipo de recurso')
    cy.get('#description').type('Descripcion corta 1')
    cy.get('#educational-resource-type-form button[type="submit"].btn.btn-primary').click()
    cy.get('.toastify').should('contain', 'Tipo de recurso educativo añadido correctamente')
  });

  it('10.Crear Tipos de recursos educativos - fallido', () => {   
        
    cy.get('#main-menu > :nth-child(2) > :nth-child(1)').click()
    cy.get(':nth-child(2) > .sub-menu > ul > :nth-child(3)').click()
    cy.get('#add-educational-resource-type-btn').click()
    cy.wait(1000)
    cy.get('#name').clear()
    cy.get('#description').type('Descripcion corta 1')
    cy.get('#educational-resource-type-form button[type="submit"].btn.btn-primary').click()
    cy.get('.toastify').should('contain', 'Algunos campos son incorrectos') 
  })

  it('11.Editar Tipos de recursos educativos - Exitoso', () => {       
    // Edita Descripción     
    cy.get('#main-menu > :nth-child(2) > :nth-child(1)').click()
    cy.get(':nth-child(2) > .sub-menu > ul > :nth-child(3)').click()
    cy.get('.tabulator-row .tabulator-cell[tabulator-field="name"]:contains("Prueba Tipo de recurso")')
      .closest('.tabulator-row')
      .find('.checkbox-cell input[type="checkbox"]')
      .check()
    cy.get('.tabulator-row .tabulator-cell[tabulator-field="name"]')
      .contains('Prueba Tipo de recurso') 
      .closest('.tabulator-row') 
      .find('button.btn.action-btn') 
      .click()
    cy.get('#description').clear().type('Descripcion larga de prueba 1')   
    cy.get('#educational-resource-type-form button[type="submit"].btn.btn-primary').click()
    cy.get('.toastify').should('contain', 'Tipo de recurso educativo actualizado correctamente') 
 
  })

  it('12.Elimina Tipos de recursos educativos - Exitoso)', () => { 
    cy.get('#main-menu > :nth-child(2) > :nth-child(1)').click()
    cy.get(':nth-child(2) > .sub-menu > ul > :nth-child(3)').click()
    cy.get('.tabulator-row .tabulator-cell[tabulator-field="name"]:contains("Prueba Tipo de recurso")')
      .closest('.tabulator-row')
      .find('.checkbox-cell input[type="checkbox"]')
      .check()
    cy.get('#delete-educational-resource-type-btn').click()
    cy.get('#confirmation-modal > .modal-body').should('be.visible')
    cy.get('#confirm-button').click()
    cy.get('.toastify').should('contain', 'Tipos de recurso educativo eliminados correctamente')      

  })

  //Pruebas de Tipos de Programas //
  it('13.Crear Tipos de programas - Exitoso', () => {   
        
    cy.get('#main-menu > :nth-child(2) > :nth-child(1)').click()
    cy.get(':nth-child(2) > .sub-menu > ul > :nth-child(4)').click()
    cy.get('#add-educational-program-type-btn').click()
    cy.wait(1000)
    cy.get('#name').type('Prueba Tipos de Programas')
    cy.get('#description').type('Descripcion corta 1')
    cy.get(':nth-child(3) > .content-container > .checkbox > .inline-flex > .checkbox-switch').click()
    cy.get('#educational-program-type-form button[type="submit"].btn.btn-primary').click()
    cy.get('.toastify').should('contain', 'Tipo de programa educativo añadido correctamente')
  })

  it('14.Crear Tipos de programas - fallido', () => {   
        
    cy.get('#main-menu > :nth-child(2) > :nth-child(1)').click()
    cy.get(':nth-child(2) > .sub-menu > ul > :nth-child(4)').click()
    cy.get('#add-educational-program-type-btn').click()
    cy.wait(1000)
    cy.get('#name').clear()
    cy.get('#description').type('Descripcion corta 1')
    cy.get(':nth-child(3) > .content-container > .checkbox > .inline-flex > .checkbox-switch').click()
    cy.get('#educational-program-type-form button[type="submit"].btn.btn-primary').click()
    cy.get('.toastify').should('contain', 'Algunos campos son incorrectos') 
  })

  it('15.Editar Tipos de programas - Exitoso', () => {       
    // Edita Descripción     
    cy.get('#main-menu > :nth-child(2) > :nth-child(1)').click()
    cy.get(':nth-child(2) > .sub-menu > ul > :nth-child(4)').click()
    cy.get('.tabulator-row .tabulator-cell[tabulator-field="name"]:contains("Prueba Tipos de Programas")')
      .closest('.tabulator-row')
      .find('.checkbox-cell input[type="checkbox"]')
      .check()
    cy.get('.tabulator-row .tabulator-cell[tabulator-field="name"]')
      .contains('Prueba Tipos de Programas') 
      .closest('.tabulator-row') 
      .find('button.btn.action-btn') 
      .click()
    cy.get('#description').clear().type('Descripcion larga de prueba 1')   
    cy.get('#educational-program-type-form button[type="submit"].btn.btn-primary').click()
    cy.get('.toastify').should('contain', 'Tipo de programa educativo actualizado correctamente') 
 
  })

  it('16.Elimina Tipos de programas - Exitoso)', () => { 
    cy.get('#main-menu > :nth-child(2) > :nth-child(1)').click()
    cy.get(':nth-child(2) > .sub-menu > ul > :nth-child(4)').click()
    cy.get('.tabulator-row .tabulator-cell[tabulator-field="name"]:contains("Prueba Tipos de Programas")')
      .closest('.tabulator-row')
      .find('.checkbox-cell input[type="checkbox"]')
      .check()
    cy.get('#delete-educational-program-type-btn').click()
    cy.get('#confirmation-modal > .modal-body').should('be.visible')
    cy.get('#confirm-button').click()
    cy.get('.toastify').should('contain', 'Tipos de programa educativo eliminados correctamente')      

  })


// Pruebas Tipo de certificación //
it('17.Crear Tipos de certificación - Exitoso', () => {   
        
  cy.get('#main-menu > :nth-child(2) > :nth-child(1)').click()
  cy.get(':nth-child(2) > .sub-menu > ul > :nth-child(6)').click()
  cy.get('#add-certification-type-btn').click()
  cy.wait(1000)
  cy.get('#name').type('Prueba Tipos de certificación')
  cy.get('#category_uid') 
    .select('11803283-2cf9-481e-85a3-3db8c17fe367') 
    .should('have.value', '11803283-2cf9-481e-85a3-3db8c17fe367')
  cy.get('#description').type('Descripción corta 1')
  cy.get('#certification-type-form button[type="submit"].btn.btn-primary').click()
  cy.get('.toastify').should('contain', 'Tipo de certificación añadida correctamente')
})

it('18.Crear Tipos de certificación - Fallido', () => {   
        
  cy.get('#main-menu > :nth-child(2) > :nth-child(1)').click()
  cy.get(':nth-child(2) > .sub-menu > ul > :nth-child(6)').click()
  cy.get('#add-certification-type-btn').click()
  cy.wait(1000)
  cy.get('#name').clear()
  cy.get('#category_uid') 
    .select('Naturaleza') 
    .should('have.value', '11803283-2cf9-481e-85a3-3db8c17fe367')
  cy.get('#description').type('Descripción corta 1')
  cy.get('#certification-type-form button[type="submit"].btn.btn-primary').click()
  cy.get('.toastify').should('contain', 'Hay errores en el formulario') 
})

it('19.Editar Tipos de certificación - Exitoso', () => {       
  // Edita Descripción     
  cy.get('#main-menu > :nth-child(2) > :nth-child(1)').click()
  cy.get(':nth-child(2) > .sub-menu > ul > :nth-child(6)').click()
  cy.get('.tabulator-row .tabulator-cell[tabulator-field="name"]:contains("Prueba Tipos de certificación")')
    .closest('.tabulator-row')
    .find('.checkbox-cell input[type="checkbox"]')
    .check()
  cy.get('.tabulator-row .tabulator-cell[tabulator-field="name"]')
    .contains('Prueba Tipos de certificación') 
    .closest('.tabulator-row') 
    .find('button.btn.action-btn') 
    .click()
  cy.get('#category_uid') 
    .select('11803283-2cf9-481e-85a3-3db8c17fe367') 
    .should('have.value', '11803283-2cf9-481e-85a3-3db8c17fe367')
  cy.get('#description').clear().type('Descripcion larga de prueba 1')   
  cy.get('#certification-type-form button[type="submit"].btn.btn-primary').click()
  cy.get('.toastify').should('contain', 'Tipo de certificación actualizada correctamente') 

})

it('20.Elimina Tipos de certificación - Exitoso)', () => { 
  cy.get('#main-menu > :nth-child(2) > :nth-child(1)').click()
  cy.get(':nth-child(2) > .sub-menu > ul > :nth-child(6)').click()
  cy.get('.tabulator-row .tabulator-cell[tabulator-field="name"]:contains("Prueba Tipos de certificación")')
    .closest('.tabulator-row')
    .find('.checkbox-cell input[type="checkbox"]')
    .check()
  cy.get('#delete-certification-type-btn').click()
  cy.get('#confirmation-modal > .modal-body').should('be.visible')
  cy.get('#confirm-button').click()
  cy.get('.toastify').should('contain', 'Tipos de certificaciones eliminados correctamente')      

})


//Competencias y resultados de aprendizaje//

it('21.Crear Competencias - Exitoso', () => {   
        
  cy.get('#main-menu > :nth-child(2) > :nth-child(1)').click()
  cy.get(':nth-child(2) > .sub-menu > ul > :nth-child(5)').click()
  cy.get('#new-competence-framework-btn').click()
  cy.wait(1000)
  cy.get('#competence-framework-form > :nth-child(1) > .content-container > #name').type('PRUEBA NUEVO MARCO DE COMPETENCIA') 
  cy.get('#competence-framework-form > :nth-child(2) > .content-container > #description').type('Descripción corta 1')
  cy.get('#is_multi_select') 
    .select('0') 
    .should('have.value', '0')
  cy.get('#competence-framework-form > .btn-block > .btn-primary').click()
  cy.get('.toastify').should('contain', 'Competencia añadida correctamente')
})

it('22.Crear Nueva Competencias - hija de la competencia anterior - Exitoso', () => {   
        
  cy.get('#main-menu > :nth-child(2) > :nth-child(1)').click()
  cy.get(':nth-child(2) > .sub-menu > ul > :nth-child(5)').click()
  cy.wait(4000)
  // Busca el checkbox correspondiente a la competencia
  cy.get('.infinite-tree-title').contains('PRUEBA NUEVO MARCO DE COMPETENCIA')
    .parents('.infinite-tree-node') 
    .find('input[type="checkbox"]') 
    .check()
  // Verifica que el checkbox está marcado
  cy.contains('PRUEBA NUEVO MARCO DE COMPETENCIA')
    .parents('.infinite-tree-node') 
    .find('input[type="checkbox"]') 
    .should('be.checked') 
    .then(() => {   
  cy.wait(2000)
  cy.get('.infinite-tree-node')
    .contains('PRUEBA NUEVO MARCO DE COMPETENCIA') 
    .parents('.infinite-tree-node') 
    .find('button.add-competence-btn[title="Añadir competencia"]').click() 
    });
  cy.get('#name').type('Competencia hija')
  cy.get('#description').type('Descripción corta 2')
  cy.get('#competence-form > .btn-block > .btn-primary').click()
  cy.get('.toastify').should('contain', 'Competencia añadida correctamente')
})

it('23.Verifica estructura jerárquica - Exitoso', () => {   
  cy.get('#main-menu > :nth-child(2) > :nth-child(1)').click()
  cy.get(':nth-child(2) > .sub-menu > ul > :nth-child(5)').click()
  cy.wait(4000)
  cy.get('.infinite-tree-title').contains('PRUEBA NUEVO MARCO DE COMPETENCIA')
    .parents('.infinite-tree-node') 
    .find('input[type="checkbox"]') 
    .check()
                  
  // Verifica la existencia del elemento principal
  cy.get('.flex.gap-1').should('exist');

  // Verifica la existencia y el contenido del título
  cy.get('.infinite-tree-title').should('exist')
      .and('include.text', 'PRUEBA NUEVO MARCO DE COMPETENCIA');
  // Verifica la existencia y los atributos de los botones
  cy.get('.edit-node-btn')
    .should('exist')
    .and('have.attr', 'title', 'Editar competencia')
    

  cy.get('.add-competence-btn')
    .should('exist')
    .and('have.attr', 'title', 'Añadir competencia')
    

  cy.get('.add-learning-result-btn')
    .should('exist')
    .and('have.attr', 'title', 'Añadir resultado de aprendizaje')    

  // Verifica la existencia y el contenido de los iconos SVG
  cy.get('.edit-node-btn svg')
    .should('exist')
    .and('have.attr', 'xmlns', 'http://www.w3.org/2000/svg');

  cy.get('.add-competence-btn svg')
    .should('exist')
    .and('have.attr', 'xmlns', 'http://www.w3.org/2000/svg');

  cy.get('.add-learning-result-btn svg')
    .should('exist')
    .and('have.attr', 'xmlns', 'http://www.w3.org/2000/svg');

  cy.get('.infinite-tree-title').contains('PRUEBA NUEVO MARCO DE COMPETENCIA')
  .parents('.infinite-tree-node') 
  .find('.infinite-tree-toggler') 
  .first()
  .click()
  })
  
it('24.Editar Nueva Competencias - hija de la competencia anterior - Exitoso', () => {   
        
  cy.get('#main-menu > :nth-child(2) > :nth-child(1)').click()
  cy.get(':nth-child(2) > .sub-menu > ul > :nth-child(5)').click()
  cy.wait(4000)
  // Busca el checkbox correspondiente a la competencia
  cy.get('.infinite-tree-title').contains('PRUEBA NUEVO MARCO DE COMPETENCIA')
    .parents('.infinite-tree-node') 
    .find('input[type="checkbox"]') 
    .check()
  // Verifica que el checkbox está marcado
  cy.contains('PRUEBA NUEVO MARCO DE COMPETENCIA')
    .parents('.infinite-tree-node') 
    .find('input[type="checkbox"]') 
    .should('be.checked') 
    .then(() => {   
  cy.wait(2000)
  cy.get('.infinite-tree-node')
    .contains('PRUEBA NUEVO MARCO DE COMPETENCIA') 
    .parents('.infinite-tree-node') 
    .find('button.edit-node-btn[title="Editar competencia"]')
    .click() 
    cy.wait(2000)
    .then(() => {
    // Verificar si la modal se abrió
    cy.get('#competence-framework-modal > .modal-body').should('be.visible')    
    //cy.get('#description').clear()
    cy.get('#competence-framework-form button[type="submit"].btn.btn-primary').click() 
    cy.get('.toastify').should('contain', 'Competencia modificada correctamente');
    })
  
  }) 


})

it('25.Edición de perfil - Exitoso)', () => { 
  cy.get('#main-menu > :nth-child(5) > :nth-child(1)').click()
  cy.get(':nth-child(5) > .sub-menu > ul > li').click()
  cy.get('#nif').clear()
  cy.get('#nif').type('61515020W')
  cy.get(':nth-child(7) > .content-container > :nth-child(1) > .inline-flex > .checkbox-switch').click()
  cy.get('#user-profile-form button[type="submit"].btn.btn-primary').click() 
  cy.get('.toastify').should('contain', 'Tu perfil se ha actualizado correctamente')

})

it('26.Edición de perfil - fallido)', () => { 
  //Nif errado
  cy.get('#main-menu > :nth-child(5) > :nth-child(1)').click()
  cy.get(':nth-child(5) > .sub-menu > ul > li').click()
  cy.get('#nif').clear()
  cy.get('#nif').type('61515020A')
  cy.get(':nth-child(7) > .content-container > :nth-child(1) > .inline-flex > .checkbox-switch').click()
  cy.get('#user-profile-form button[type="submit"].btn.btn-primary').click() 
  cy.get('.toastify').should('contain', 'Algunos campos son incorrectos')

});
})