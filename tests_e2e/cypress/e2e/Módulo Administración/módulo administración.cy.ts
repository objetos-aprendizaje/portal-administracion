// <reference types="cypress" />
Cypress.on('uncaught:exception', () => false),
describe('Módulo Administración', () => {
  beforeEach(() => {
   
    cy.visit(Cypress.env('baseUrl_back'))
    cy.get('input[name="email"]').type(Cypress.env('email_admin')) 
    cy.get('input[name="password"]').type(Cypress.env('contrasena')) 
    cy.get(':nth-child(2) > .btn').click() 
 
    
  })

  afterEach(() => {
    
    cy.wait(1000)
    //cy.get('body').screenshot({ overwrite: true, capture: 'fullPage' })
    cy.screenshot({ 
      overwrite: true,
      capture: 'fullPage',
      clip: { x: 0, y: 0, width: 1920, height: 1080 }
    });
    
   
  })

  function restaurarColoresPredeterminados(colorId, restauraColor) {

    function cambiarColorPorId() {
      cy.get(colorId).click(); // Hacer clic en el elemento con el ID especificado
      cy.get('#clr-color-value').clear().type(nuevoColor); // Escribir el nuevo color en el input
    }
    cy.visit(Cypress.env('baseUrl_back')) // Visita la página de administración
    cy.get('#main-menu > :nth-child(1) > :nth-child(1) > span').click()
    cy.get(':nth-child(1) > .sub-menu > ul > :nth-child(1) > a').click()  
    cy.get(colorId).click() // Hacer clic en el elemento con el ID especificado
    cy.wait(500)
    cy.get('#clr-color-value').clear()
    cy.get('#clr-color-value').focus()
    cy.get('#clr-color-value').trigger('input')
    cy.get('#clr-color-value').type(restauraColor)
    // restablece colores
    cy.get('#update-colors-btn').click()
   
  }

 
  it('1.Verificar cambios logotipo correcto', () => {   
    // Rol Administrador
    
    cy.get('#main-menu > :nth-child(1) > :nth-child(1) > span').click()
    cy.get(':nth-child(1) > .sub-menu > ul > :nth-child(1) > a').click()  
    // Ruta de la imagen 
    const imagePath = '/logologin-1721650225-1721849673.jpg';

    // Subir la imagen
    //cy.get('#logo-poa').attachFile('logo_login.jpg') 
    cy.get('input[type="file"]#poa_logo_1').attachFile(imagePath);

    // Verificar que el contenedor de la imagen se muestre
    cy.get('#logo_container_poa_logo_1').should('be.visible')
    
  });

  it('2.Verificar cambios en los colores de la interfaz- Color Primario (Títulos y Botones)', () => {   
    
    //Color Primario (Títulos y Botones)
    cy.get('#main-menu > :nth-child(1) > :nth-child(1) > span').click()
    cy.get(':nth-child(1) > .sub-menu > ul > :nth-child(1) > a').click() 
    //sección colores
    cy.get('#color-1').click()
    cy.wait(500)
    cy.get('#clr-color-value').clear()
    cy.get('#clr-color-value').focus()
    cy.get('#clr-color-value').trigger('input')
    //cambia color 
    cy.get('#clr-color-value').type('#2c7d7d')    
    // Guardar configuración
    cy.get('#update-colors-btn').click()
    //Visita la interfaz - verifica cambio de color 
    cy.visit(Cypress.env('baseUrl'))
    cy.wait(1000)
    //Restaura colores de Color Primario (Títulos y Botones)
    restaurarColoresPredeterminados('#color-1', '#2c4c7e')
           
  });

  it('3. Verificar cambios en los colores de la interfaz- Color Secundario (Subtítulos y Botones Hover))', () => {   
   
    //Color Secundario (Subtítulos y Botones Hover)
    cy.get('#main-menu > :nth-child(1) > :nth-child(1) > span').click()
    cy.get(':nth-child(1) > .sub-menu > ul > :nth-child(1) > a').click() 
    //sección colores
    cy.get('#color-2').click()
    cy.wait(500)
    cy.get('#clr-color-value').clear()
    cy.get('#clr-color-value').focus()
    cy.get('#clr-color-value').trigger('input')
    //cambia color a negro
    cy.get('#clr-color-value').type('#000000')    
    // Guardar configuración
    cy.get('#update-colors-btn').click()
    //Visita la interfaz - verifica cambio de color 
    cy.visit(Cypress.env('baseUrl'))  
    //Muestra la sección donde se realizó el cambio de background a negro
    cy.get('.bg-color_2 > .container > .hidden')
    .should('be.visible')
    .and('have.text', '¿Qué quieres aprender hoy?')
    cy.screenshot('cambio color-aprendizaje')  
    cy.wait(1000)
    //Restaura colores de Color Primario (Títulos y Botones)
    restaurarColoresPredeterminados('#color-2', '#507ab9')
           
  });

  it('4.General- Verificar cambios en los colores de la interfaz- Color Terciario (Textos Principales)', () => {   
    
    //Color Terciario (Textos Principales)
    cy.get('#main-menu > :nth-child(1) > :nth-child(1) > span').click()
    cy.get(':nth-child(1) > .sub-menu > ul > :nth-child(1) > a').click() 
    //sección colores
    cy.get('#color-3').click()
    cy.wait(500)
    cy.get('#clr-color-value').clear()
    cy.get('#clr-color-value').focus()
    cy.get('#clr-color-value').trigger('input')
    //cambia color a naranja a textos principales del footer
    cy.get('#clr-color-value').type('#cc4733')    
    // Guardar configuración
    cy.get('#update-colors-btn').click()
    //Visita la interfaz - verifica cambio de color en el footer
    cy.visit(Cypress.env('baseUrl'))
    cy.screenshot('cambio color-aprendizaje') 
    cy.wait(1000)
    cy.get('footer.bg-white')
    .should('be.visible')    
    cy.screenshot('cambio color-Textos Principales') 
    //Restaura colores de Color Primario (Títulos y Botones)
    restaurarColoresPredeterminados('#color-3', '#1f1f20')
           
  });

  it('5.General- Verificar cambios en los colores de la interfaz- Color Cuaternario (Textos Secundarios)', () => {   
    
    //Color Terciario (Textos Principales)
    cy.get('#main-menu > :nth-child(1) > :nth-child(1) > span').click()
    cy.get(':nth-child(1) > .sub-menu > ul > :nth-child(1) > a').click() 
    //sección colores
    cy.get('#color-4').click()
    cy.wait(500)
    cy.get('#clr-color-value').clear()
    cy.get('#clr-color-value').focus()
    cy.get('#clr-color-value').trigger('input')
    //cambia color a naranja a textos principales del footer
    cy.get('#clr-color-value').type('#5c0cb8')    
    // Guardar configuración
    cy.get('#update-colors-btn').click()
    //Visita la interfaz - verifica cambio de color 
    cy.visit(Cypress.env('baseUrl'))
    cy.get('.items-center p a[href="/searcher?resources=courses"]')
      .should('be.visible')
    cy.wait(1000)      
    cy.screenshot('cambio color-Textos secundarios') 
    //Restaura colores de Color Primario (Títulos y Botones)
    restaurarColoresPredeterminados('#color-4', '#585859')
           
  });

  it('6.Verificar configuración del servidor SMTP - Exitoso)', () => {   
    
   
    cy.get('#main-menu > :nth-child(1) > :nth-child(1) > span').click()
    cy.get(':nth-child(1) > .sub-menu > ul > :nth-child(1) > a').click() 
    //sección configuración SMTP
   
    cy.get('input#smtp_server.poa-input').should('be.visible') 
      .invoke('val') 
      .should('have.length.greaterThan', 0)
    cy.get('input#smtp_port.poa-input').should('be.visible') 
      .invoke('val') 
      .should('have.length.greaterThan', 0)
    cy.get('input#smtp_user.poa-input').should('be.visible') 
      .invoke('val') 
      .should('have.length.greaterThan', 0)
    cy.get('input#smtp_password').should('be.visible') 
      .invoke('val') 
      .should('have.length.greaterThan', 0)
    cy.get('input#smtp_name_from').should('be.visible') 
      .invoke('val') 
      .should('have.length.greaterThan', 0)
    cy.get('input#smtp_name_from').should('be.visible') 
      .invoke('val') 
      .should('have.length.greaterThan', 0) 

    cy.get('#email-server-form > .poa-form > .btn').click()
    cy.get('.toastify').should('contain', 'Servidor de correo guardado correctamente')

  })

  it('7.Verificar configuración del servidor SMTP - Fallido)', () => {   
    
  
    cy.get('#main-menu > :nth-child(1) > :nth-child(1) > span').click()
    cy.get(':nth-child(1) > .sub-menu > ul > :nth-child(1) > a').click() 
    //sección configuración SMTP

    cy.get('input#smtp_server.poa-input').clear()
    cy.get('#smtp_port').should('be.visible') 
    cy.get('#smtp_user').should('be.visible') 
    cy.get('#smtp_user').should('be.visible') 
    cy.get('#smtp_password').should('be.visible') 
    cy.get('#smtp_name_from').should('be.visible') 
    cy.get('#email-server-form > .poa-form > .btn').click()
    cy.get('.toastify').should('contain', 'Algunos campos son incorrectos')

  });

  it('8.Verificar Envío de sugerencias y mejoras - Exitoso)', () => {   
    

    cy.get('#main-menu > :nth-child(1) > :nth-child(1) > span').click()
    cy.get(':nth-child(1) > .sub-menu > ul > :nth-child(6)').click() 
    //Sección Envío de sugerencias y mejoras
    cy.get('input#email-input').type('email@example.com')
    cy.get('#add-email-btn').click()
    cy.get('.toastify').should('contain', 'Email añadido correctamente')

  });

  it('9.Elimina correo de envío de sugerencias y mejoras - Exitoso)', () => { 
    cy.get('#main-menu > :nth-child(1) > :nth-child(1) > span').click()
    cy.get(':nth-child(1) > .sub-menu > ul > :nth-child(6)').click() 
    cy.get('.tabulator-row .tabulator-cell[tabulator-field="email"]:contains("email@example.com")')
      .closest('.tabulator-row')
      .find('.checkbox-cell input[type="checkbox"]')
      .check();
      cy.get('#btn-delete-emails').click()
      cy.get('#confirmation-modal > .modal-body').should('be.visible')
      cy.get('#confirm-button').click()
      cy.get('.toastify').should('contain', 'Emails eliminados correctamente')      

  });
  it('10.Verificar Permiso a Gestores - Exitoso)', () => { 
    cy.get('#main-menu > :nth-child(1) > :nth-child(1) > span').click()
    cy.get(':nth-child(1) > .sub-menu > ul > :nth-child(3)').click()
    cy.get(':nth-child(1) > .inline-flex > .checkbox-switch').click()
    cy.get('#managers-permissions-form > .btn').click()
    cy.get('.toastify').should('contain', 'Permisos guardados correctamente')

  });

 
});

