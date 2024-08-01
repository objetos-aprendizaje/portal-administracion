declare namespace Cypress {
    interface Chainable<Subject> {
        login(username: any, password: any): Chainable<any>
        loginAdmin(): Chainable<any>
        upload_file(fileName: any, fileType?: string, selector: any): Chainable<any>
  }
}