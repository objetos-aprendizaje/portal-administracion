const { defineConfig } = require("cypress");

module.exports = defineConfig({ 
  screenshotOnRunFailure:true,
  reporter: 'cypress-mochawesome-reporter',
  reporterOptions: {
    reportDir: 'cypress/reports',
    charts: true,
    reportPageTitle: 'Informe Pruebas Unitarias POA - Log',
    embeddedScreenshots: false,
    inlineAssets: true,
    saveAllAttempts: false,
    // screenshotWidth: 500,
    // screenshotHeight: 500,
    imageMinifierOptions: {
      plugins: [
        ["gifsicle", { interlaced: true }],
        ["jpegtran", { progressive: true }],
        ["optipng", { optimizationLevel: 5 }],
        ["svgo", {
          plugins: [
            {
              removeViewBox: true
            }
          ]
        }]
      ]
    }
  }, 
  "pageLoadTimeout": 120000,
  e2e: {
    baseUrl: 'https://portalobjetosaprendizaje.devmainjobs.com',
    setupNodeEvents(on, config) {
      // implement node event listeners here     
        require('cypress-mochawesome-reporter/plugin')(on);      
      
    },
  },
  env: {
    // login
    baseUrl: 'https://portalobjetosaprendizaje.devmainjobs.com',
    baseUrl_back:'https://admin.portalobjetosaprendizaje.devmainjobs.com',
    baseUrl_profile:"https://portalobjetosaprendizaje.devmainjobs.com/profile/update_account",
    login_url: '/login',
    //estudiante
    email_estudiante: 'ramonestudiante@um.es',
    contrasena: 'ifureMNovEOL',
    contrasenaincorrecta: '65323232',
    correct_login: '/user/1?check_logged_in=1',
    restore_pass: '/user/password',   
    //Gestor
    email_gestor: 'paulagestora@um.es',
    //Administrador
    email_admin: 'pedroadministrador@um.es',
    //Docente
    email_docente: 'marcosdocente@um.es',
    

   
  },

  "viewportWidth": 1920,
  "viewportHeight": 1080


  

});
