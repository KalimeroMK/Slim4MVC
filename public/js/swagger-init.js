// Boots Swagger UI on the API documentation page.
//
// This was an inline <script>, which script-src 'self' does not allow, so the page
// rendered an empty container and nothing else. Same configuration, in a file.
/* global SwaggerUIBundle, SwaggerUIStandalonePreset */
window.addEventListener('load', function () {
    SwaggerUIBundle({
        url: '/swagger.json',
        dom_id: '#swagger-ui',
        deepLinking: true,
        presets: [
            SwaggerUIBundle.presets.apis,
            SwaggerUIStandalonePreset,
        ],
        plugins: [
            SwaggerUIBundle.plugins.DownloadUrl,
        ],
        layout: 'StandaloneLayout',
        validatorUrl: null,
        supportedSubmitMethods: ['get', 'post', 'put', 'delete', 'patch'],
    });
});
