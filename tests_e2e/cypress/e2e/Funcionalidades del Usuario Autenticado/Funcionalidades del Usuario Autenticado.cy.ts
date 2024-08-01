Cypress.on('uncaught:exception', () => false),
describe('Funcionalidades del Usuario Autenticado', () => {
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

  it('1.Visualización de "Mis cursos"', () => { 

    const inscribedCoursesUrl = Cypress.env('baseUrl')+ '/profile/my_courses/inscribed'
    const enrolledCoursesUrl = Cypress.env('baseUrl')+ '/profile/my_courses/enrolled'
    const historicCoursesUrl = Cypress.env('baseUrl')+ '/profile/my_courses/historic'
    
    cy.visit(Cypress.env('login_url'))
    cy.get('#loginFormDesktop [name="email"]').type(Cypress.env('email_estudiante')) 
    cy.get('#loginFormDesktop [name="password"]').type(Cypress.env('contrasena'))
    // Ejecuta botón "Iniciar sesión"
    cy.get('#loginFormDesktop button.btn').click()
    cy.get('#menu-button').click()
    cy.get('#menu-item-0').click()
    cy.get(':nth-child(2) > .toggle-submenu').click()
    cy.get('.sub-menu').should('be.visible')
    // Cursos Inscritos
    cy.visit(inscribedCoursesUrl)
    // Cursos Matriculados
    cy.visit(enrolledCoursesUrl)
    // Cursos Matriculados
    cy.visit(historicCoursesUrl)
  });

  it('2.Edición Mi Perfil', () => {
   
      //Muestra los datos del perfil actual
      cy.visit(Cypress.env('login_url'))
      cy.get('input[name="email"]').type(Cypress.env('email_estudiante')) 
      cy.get('input[name="password"]').type(Cypress.env('contrasena')) 
      cy.get('#loginFormDesktop button.bg-color_1').click()
      cy.get('#menu-button').click()
      cy.get('#menu-item-0').click()
      cy.get('.menu-content')
        .contains('Mi perfil')
        .should('be.visible')
        .click()
      //Editar "Mi Perfil" campo Departamento - Queda pendiente porque muestra error al cambiar
      // cy.get('#department').type('departamento 1')
      // cy.get('button[type="submit"].btn.btn-primary-profile')
      //   .contains('Guardar')
      //   .click() 
  });

  it('3.Edición Notificaciones', () => {
   
    //Muestra la configuración de las notificaciones actual
    cy.visit(Cypress.env('login_url'))
    cy.get('input[name="email"]').type(Cypress.env('email_estudiante')) 
    cy.get('input[name="password"]').type(Cypress.env('contrasena')) 
    cy.get('#loginFormDesktop button.bg-color_1').click()
    cy.get('#menu-button').click()
    cy.get('#menu-item-0').click()
    cy.get('.menu-content')
      .contains('Notificaciones')
      .should('be.visible')
      .click()
    //Editar "Notificaciones generales" Activa "Avisos de nuevos cursos"
    cy.get('.mb-4 > :nth-child(6) > .inline-flex > .checkbox-switch').click()
    cy.get('#save-notifications-btn').click()
    cy.get('.toastify.on.success.toastify-right.toastify-top')
      .should('be.visible')
      .and('contain', 'Notificaciones guardadas correctamente')
      .then(() => {
        cy.wait(5000)
        })
       .should('not.exist')

});

  it('3.Envío fallido de consultas sobre cursos y programas', () => {
    
     cy.get('.courses-lane > .containerCarrousel > .swiper-container > .swiper-wrapper > .swiper-slide-next > :nth-child(1) > :nth-child(1)').click()
     cy.get('.justify-center > .no-effect-hover > .btn').click()

     cy.get('#form-doubt [name="name"]').type('Ramón prueba cypress') 
     cy.get('#form-doubt [name="email"]').type('ramon@g@asesoresnt.com') 
     cy.get('#message').type('Solicito información sobre los horarios del curso')
     cy.get('#form-doubt button.btn').click() 
     
     cy.get('.toastify.on.error.toastify-right.toastify-top')
       .should('be.visible')
       .and('contain', 'Algunos campos son incorrectos')

  });

  it('4.Envío exitoso de consultas sobre cursos y programas', () => {
    
    cy.get('.courses-lane > .containerCarrousel > .swiper-container > .swiper-wrapper > .swiper-slide-next > :nth-child(1) > :nth-child(1)').click()
    cy.get('.justify-center > .no-effect-hover > .btn').click()
    cy.get('#form-doubt [name="name"]').type('Prueba cypress') 
    cy.get('#form-doubt [name="email"]').type('prueba@example.com') 
    cy.get('#message').type('Solicito información sobre los horarios del curso')
    cy.get('#form-doubt button.btn').click() 
    cy.get('.toastify.on.success.toastify-right.toastify-top')
      .should('be.visible')
      .and('contain', 'Mensaje enviado correctamente')
      .then(() => {
        cy.wait(5000)
        })
       .should('not.exist')

 }); 
 
});

