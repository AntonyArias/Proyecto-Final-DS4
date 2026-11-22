// Ejecutar con swagger-parser instalado en el directorio temporal indicado en NODE_PATH.
const SwaggerParser = require('@apidevtools/swagger-parser');
const path = require('node:path');
SwaggerParser.validate(path.join(__dirname, '../public/openapi.json'))
  .then(spec => {
    const operations = Object.values(spec.paths).reduce((n, p) => n + Object.keys(p).length, 0);
    console.log(`OK: OpenAPI ${spec.openapi}; ${operations} operaciones validadas.`);
  })
  .catch(error => { console.error(error.message); process.exitCode = 1; });
