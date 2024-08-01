const currentDate = new Date().toLocaleString().replace(/[^\d\s]/g, '')
Cypress.on('uncaught:exception', () => false),

describe('Verificar la navegación', () => {

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

  it.only('Verifica la navegación por botones menú principal', () => {
    cy.url().should('contain', Cypress.env('baseUrl'))  
    //Enlace de la sección "Buscador"  
    cy.get('.items-center > [href="'+ Cypress.env('baseUrl')+'/searcher"]').click()
    // Verifica que la URL haya cambiado a la sección correspondiente
    cy.url().should('contain', Cypress.env('baseUrl')+'/searcher')
    //cy.url().should('eq', + Cypress.env('baseUrl')+'/searcher')
    // Enlace de la sección "login"
    cy.get('.border-color_1').click()
    // Verifica que la URL haya cambiado a la sección correspondiente
    cy.url().should('contain', Cypress.env('baseUrl')+'/login')
    //cy.url().should('eq', + Cypress.env('baseUrl')+'/login')
    cy.visit('/')
    // Haz clic en el enlace de la sección "Registrame" ESTA PENDIENTE
    cy.get('.button-register').click();
    // Verifica que la URL haya cambiado a la sección correspondiente
    cy.url().should('eq', 'https://portalobjetosaprendizaje.devmainjobs.com/#');

  
  });

  
  it.only('Verifica la navegación por diferentes secciones del Home', () => {
     // Verifica que la URL sea correcta
     cy.url().should('contain', Cypress.env('baseUrl'))  
     //Enlace de la sección "Cursos destacados - Ver todos los cursos"  
     cy.get('.items-center p a[href="/searcher?resources=courses"]')
       .should('be.visible')
       .and('have.attr', 'href', '/searcher?resources=courses')
       .click()
    // cy.get('.items-center > [href="/searcher?resources=courses"]').click()
     // Verifica que la URL haya cambiado a la sección correspondiente
     cy.url().should('contain', Cypress.env('baseUrl')+'/searcher?resources=courses')
    // cy.url().should('eq', + Cypress.env('baseUrl')+'/searcher?resources=courses')
     cy.visit('/')
    //Enlace de la sección "Programas destacados - Ver todos los programas"  
    cy.get('.items-center p a[href="/searcher?resources=programs"]')
       .should('be.visible')
       .and('have.attr', 'href', '/searcher?resources=programs')
       .click()
    // Verifica que la URL haya cambiado a la sección correspondiente
    cy.url().should('contain', Cypress.env('baseUrl')+'/searcher?resources=programs')
    //cy.url().should('eq', + Cypress.env('baseUrl')+'/searcher?resources=programs')
    cy.visit('/')
    //Enlace de la sección "Recursos destacados - Ver todos los recursos"  
    cy.get('.items-center p a[href="/searcher?resources=resources"]')
       .should('be.visible')
       .and('have.attr', 'href', '/searcher?resources=resources')
       .click()
    // Verifica que la URL haya cambiado a la sección correspondiente
    cy.url().should('contain', Cypress.env('baseUrl')+'/searcher?resources=resources')
    // cy.url().should('eq', + Cypress.env('baseUrl')+ '/searcher?resources=resources')
    cy.visit('/')
    // Enlace de la sección ¿Qué quieres aprender hoy?
    cy.get('.bg-color_2 > .container > .hidden')
    .should('be.visible')
    .and('have.text', '¿Qué quieres aprender hoy?')
    cy.get('.grid >a[href="/searcher?category_uid=11803283-2cf9-481e-85a3-3db8c17fe367"]')
    .click()
    // Verifica que la URL haya cambiado a la sección correspondiente
    cy.url()
  .should('include', '/searcher?category_uid=11803283-2cf9-481e-85a3-3db8c17fe367')
  });

  it.only('Verifica que los elementos de las secciones responden a las interacciones del usuario', () => {
    // Sección Cursos destacados
    cy.get('.items-center p a[href="/searcher?resources=courses"]')
       .should('be.visible')
       .and('have.attr', 'href', '/searcher?resources=courses')
    cy.get('.courses-lane > .containerCarrousel > .swiper-container > .swiper-wrapper > .swiper-slide-next > :nth-child(1) > :nth-child(1)').click()
    cy.get('[href="'+ Cypress.env('baseUrl')+'"]').click()

    // Sección programas destacados
 
    // Sección recursos destacados
    cy.get('.resources-lane > .containerCarrousel > .swiper-container > .swiper-wrapper > .swiper-slide')
  .should('have.length.gt', 0)
  .then(($slides) => {
    if ($slides.length > 0) {
      cy.get('.resources-lane > .containerCarrousel > .swiper-container > .swiper-wrapper > .swiper-slide-next > :nth-child(1) > :nth-child(1)').click()
      cy.get('[href="'+ Cypress.env('baseUrl')+'"]').click()
    } else {
      cy.log('La sección de recursos destacados no tiene información, omitiendo los pasos de la prueba.')
    }
  });
});

    // Prueba en los diferentes navegadores
     // Chrome
    it('Verifica la visualización en Chrome', () => {
      cy.browser('chrome')
      cy.visit('/')
      cy.get('header').should('be.visible')
      cy.get('nav').should('be.visible')
      cy.get('main').should('be.visible')
      cy.get('footer').should('be.visible')
    })
  
    // Safari
    it('Verifica la visualización en Safari', () => {
      cy.browser('webkit')
      cy.visit('/')
      cy.get('header').should('be.visible')
      cy.get('nav').should('be.visible')
      cy.get('main').should('be.visible')
      cy.get('footer').should('be.visible')
    })
  
    // Opera
    it('Verifica la visualización en Opera', () => {
      cy.browser('opera')
      cy.visit('/')
      cy.get('header').should('be.visible')
      cy.get('nav').should('be.visible')
      cy.get('main').should('be.visible')
      cy.get('footer').should('be.visible')
    })
  
    // Firefox
    it('Verifica la visualización en Firefox', () => {
      cy.browser('firefox')
      cy.visit('/')
      cy.get('header').should('be.visible')
      cy.get('nav').should('be.visible')
      cy.get('main').should('be.visible')
      cy.get('footer').should('be.visible')
    })


  // Prueba diferentes dipositivos
  it.only('Verifica la visualización en diferentes tamaños de pantalla', () => {
    // Escritorio
    cy.viewport(1024, 768)
    cy.visit('/')
    cy.get('header').should('be.visible')
    cy.get('section').should('be.visible')
    cy.get('footer').should('be.visible')

    // Tablet
    cy.viewport('ipad-2')
    cy.visit('/')
    cy.get('header').should('be.visible')
    cy.get('section').should('be.visible')
    cy.get('footer').should('be.visible')

    // Móvil
    cy.viewport('iphone-6')
    cy.visit('/')
    cy.get('header').should('be.visible')
    cy.get('section').should('be.visible')
    cy.get('footer').should('be.visible')
  })

});


 
