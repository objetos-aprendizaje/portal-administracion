const currentDate = new Date().toLocaleString().replace(/[^\d\s]/g, '')
Cypress.on('uncaught:exception', () => false),

describe('Busqueda-notificaciones', () => {

  beforeEach(() => {
    cy.visit('/'), {
      headers: {       
      },
      failOnStatusCode: false            
    };
})

  
  afterEach(() => {
    cy.wait(1000)
    cy.get('body').screenshot({ overwrite: true}) 
    
  })

  it('Buscar un término y ver los resultados', () => {
    const searchUrl = Cypress.env('baseUrl')+'/searcher'
    
    cy.get('.items-center > [href="'+ searchUrl+'"]').click()
    cy.get('#search').type('Programación');

    //Desmarca botón recursos
    cy.get('button[for="programs"]:contains("Programa")').click();
    //Verificar que el checkbox de "Programs" esté desmarcado
    cy.get('button[for="programs"]:contains("Programa") .checkbox-icon').should('not.have.class', 'unchecked')
    // Se Presiona Enter para realizar la búsqueda
    cy.get('#search').type('{enter}') 
    // Verificar que el número de resultados filtrados sea mayor que 1
    cy.get('#filter-results-total').should('have.text', '1').and('not.have.text', '0')   

  })

   it('Mensaje de error si no hay resultados en la búsqueda', () => {
    cy.get('.items-center > [href="https://portalobjetosaprendizaje.devmainjobs.com/searcher"]').click()
      // Ingresa un término de búsqueda que no debería tener resultados
      cy.get('#search').type('Término_Inexistente');
  
     // Presiona Enter para realizar la búsqueda
      cy.get('#search').type('{enter}');
  
      // Verifica que se muestre un mensaje de error
      cy.get('#no-learning-objects-found').should('be.visible');
      cy.get('#no-learning-objects-found').should('contain', 'No se encontraron objetos de aprendizaje');
    });

  it('Verifica filtro de cursos, programas y recursos en sección Nuestros cursos más destacados', () => {
    //Los botones del filtro por defecto estan tildados, se desactivar botones del filtro 
    cy.get('#cursoButton').click()
    cy.get('#programaButton').click()
    cy.get('#recursoButton').click()
    //Filtro Cursos
    cy.get('#cursoButton').click()
    cy.wait(4000)
    //Filtro Programas
    cy.get('#cursoButton').click()
    cy.wait(4000)
    cy.get('#programaButton').click()
    cy.wait(4000)
    //Filtro Recursos
    cy.get('#programaButton').click()
    cy.wait(4000)
    cy.get('#recursoButton').click()
    cy.wait(4000)


  })

  it('Visualización de notificaciones en el portal', () => {
    cy.visit(Cypress.env('login_url'))
    cy.get('#loginFormDesktop [name="email"]').type(Cypress.env('email_estudiante')) 
    cy.get('#loginFormDesktop [name="password"]').type(Cypress.env('contrasena'))
    // Ejecuta botón "Iniciar sesión"
    cy.get('#loginFormDesktop button.btn').click()
    //ícono de notificación     
    cy.get('#bell-btn').click()
    //Verifica que se muestre el mensaje de notificación
    cy.get('#notification-box').should('be.visible')
    cy.get('#notification-box').should('contain', 'Sin notificaciones')
    
  })



})