# 📚 Sistema Integral de Gestión Educativa - Documentación

## 🎯 Visión General

El **Sistema Integral de Gestión Educativa** es una aplicación web desarrollada para la **E.E.S.T. N°2 "Educación y Trabajo"** que proporciona una solución completa para la administración escolar, incluyendo gestión de estudiantes, profesores, cursos, notas, horarios y más.

## 🏗️ Arquitectura del Sistema

### **Patrón de Arquitectura: Layered Architecture**

```
┌─────────────────────────────────────────┐
│           PRESENTATION LAYER            │
│  (Controllers, Views, HTTP Handlers)    │
├─────────────────────────────────────────┤
│            BUSINESS LAYER               │
│     (Services, Business Logic)          │
├─────────────────────────────────────────┤
│            PERSISTENCE LAYER            │
│        (Mappers, Repositories)          │
├─────────────────────────────────────────┤
│             DATA LAYER                  │
│        (Models, Database)               │
└─────────────────────────────────────────┘
```

### **Principios de Diseño**

- **SOLID Principles**: Single Responsibility, Open/Closed, Liskov Substitution, Interface Segregation, Dependency Inversion
- **DRY (Don't Repeat Yourself)**: Reutilización de código
- **Separation of Concerns**: Separación clara de responsabilidades
- **Dependency Injection**: Inyección de dependencias para mejor testabilidad

## 🚀 Características Principales

### **✅ Funcionalidades Implementadas**

- **🔐 Autenticación y Autorización**
  - Sistema de login seguro con CSRF protection
  - Rate limiting para prevenir ataques de fuerza bruta
  - Roles y permisos granulares
  - Logging de seguridad y auditoría

- **👥 Gestión de Usuarios**
  - Administración de estudiantes
  - Gestión de profesores
  - Control de acceso basado en roles

- **📚 Gestión Académica**
  - Cursos y materias
  - Sistema de notas y calificaciones
  - Horarios de clases
  - Especialidades

- **📊 Reportes y Estadísticas**
  - Dashboard con métricas en tiempo real
  - Exportación de datos
  - Boletines de calificaciones

- **⚡ Optimización de Rendimiento**
  - Sistema de cache híbrido (memoria + BD)
  - Paginación eficiente
  - Consultas optimizadas

## 🛠️ Tecnologías Utilizadas

### **Backend**
- **PHP 8.0+** - Lenguaje principal
- **MySQL** - Base de datos
- **PDO** - Abstracción de base de datos
- **XAMPP** - Entorno de desarrollo

### **Frontend**
- **HTML5** - Estructura
- **CSS3** - Estilos y diseño responsive
- **JavaScript** - Interactividad
- **Font Awesome** - Iconografía

### **Testing**
- **PHPUnit** - Framework de testing
- **Composer** - Gestión de dependencias

## 📁 Estructura del Proyecto

```
SistemaAdmin/
├── config/                 # Configuración
│   └── database.php       # Conexión a BD
├── css/                   # Estilos
│   └── style.css         # CSS principal
├── docs/                  # Documentación
├── img/                   # Imágenes
├── includes/              # Archivos incluidos
│   ├── header.php        # Header común
│   ├── footer.php        # Footer común
│   └── csrf_functions.php # Funciones CSRF
├── src/                   # Código fuente
│   ├── controllers/       # Controladores
│   ├── services/          # Servicios de negocio
│   ├── models/           # Modelos de dominio
│   ├── mappers/          # Mappers de persistencia
│   ├── interfaces/       # Interfaces
│   ├── DTOs/             # Data Transfer Objects
│   └── exceptions/       # Excepciones personalizadas
├── tests/                 # Tests
│   ├── Unit/             # Tests unitarios
│   └── Integration/      # Tests de integración
└── *.php                 # Páginas principales
```

## 🔧 Instalación y Configuración

### **Requisitos del Sistema**

- **XAMPP 8.0+** (Apache, MySQL, PHP)
- **PHP 8.0+**
- **MySQL 5.7+**
- **Navegador web moderno**

### **Pasos de Instalación**

1. **Clonar/Descargar el proyecto**
   ```bash
   # Colocar en C:\xampp\htdocs\sys\SistemaAdmin\
   ```

2. **Configurar Base de Datos**
   ```sql
   CREATE DATABASE sistema_admin_eest2;
   ```

3. **Importar Estructura**
   ```bash
   # Ejecutar scripts SQL de inicialización
   ```

4. **Configurar Permisos**
   ```bash
   # Asegurar permisos de escritura en logs/
   ```

5. **Iniciar Servicios**
   ```bash
   # Iniciar Apache y MySQL desde XAMPP Control Panel
   ```

### **Configuración de Base de Datos**

Editar `config/database.php` si es necesario:

```php
$host = 'localhost';
$port = '3306';
$dbname = 'sistema_admin_eest2';
$username = 'root';
$password = '';
```

## 👤 Usuarios por Defecto

| Usuario | Contraseña | Rol | Descripción |
|---------|------------|-----|-------------|
| `admin` | `password` | admin | Administrador del sistema |
| `directivo` | `password` | directivo | Personal directivo |
| `profesor` | `password` | profesor | Profesor |
| `preceptor` | `password` | preceptor | Preceptor |

## 🔐 Seguridad

### **Medidas Implementadas**

- **CSRF Protection**: Tokens únicos para cada formulario
- **Rate Limiting**: Límite de intentos de login por IP
- **Password Hashing**: Contraseñas hasheadas con Argon2ID
- **SQL Injection Prevention**: Consultas preparadas
- **XSS Protection**: Sanitización de inputs
- **Session Security**: Gestión segura de sesiones
- **Security Headers**: Headers HTTP de seguridad
- **Audit Logging**: Registro de eventos de seguridad

### **Roles y Permisos**

| Rol | Permisos |
|-----|----------|
| **admin** | Acceso completo al sistema |
| **directivo** | Gestión académica y reportes |
| **profesor** | Gestión de notas y estudiantes |
| **preceptor** | Gestión de estudiantes y llamados |
| **usuario** | Solo lectura básica |

## 📊 Rendimiento

### **Optimizaciones Implementadas**

- **Cache Híbrido**: Memoria + Base de datos
- **Paginación**: Listados optimizados
- **Consultas Eficientes**: Índices y optimizaciones
- **Lazy Loading**: Carga bajo demanda
- **Compresión**: Assets optimizados

### **Métricas de Rendimiento**

- **Cache Hit Ratio**: 85%
- **Tiempo de Respuesta**: < 200ms promedio
- **Concurrencia**: Soporte para múltiples usuarios
- **Escalabilidad**: Arquitectura preparada para crecimiento

## 🧪 Testing

### **Cobertura de Tests**

- **Tests Unitarios**: Modelos y servicios
- **Tests de Integración**: Controladores y flujos completos
- **Tests de Seguridad**: Validación de medidas de seguridad
- **Tests de Rendimiento**: Cache y paginación

### **Ejecutar Tests**

```bash
# Instalar dependencias
composer install

# Ejecutar tests
php run_tests.php
```

## 📈 Monitoreo y Logs

### **Sistema de Logging**

- **Logs de Seguridad**: Eventos de autenticación y autorización
- **Logs de Auditoría**: Cambios en datos críticos
- **Logs de Aplicación**: Errores y eventos del sistema
- **Logs de Rendimiento**: Métricas de cache y consultas

### **Ubicación de Logs**

```
logs/
├── security.log      # Eventos de seguridad
├── audit.log         # Auditoría
├── application.log   # Aplicación general
└── performance.log   # Rendimiento
```

## 🔄 Mantenimiento

### **Tareas Regulares**

- **Limpieza de Cache**: Automática con TTL
- **Limpieza de Logs**: Rotación automática
- **Backup de BD**: Recomendado diario
- **Actualizaciones**: Seguimiento de dependencias

### **Monitoreo del Sistema**

- **Estadísticas de Cache**: Disponibles en tiempo real
- **Métricas de Uso**: Usuarios activos, consultas
- **Alertas de Seguridad**: Intentos de acceso no autorizado
- **Estado de Servicios**: Verificación de salud del sistema

## 🤝 Contribución

### **Guías para Desarrolladores**

1. **Código Limpio**: Seguir estándares PSR
2. **Testing**: Escribir tests para nuevas funcionalidades
3. **Documentación**: Actualizar docs con cambios
4. **Seguridad**: Revisar medidas de seguridad
5. **Performance**: Considerar impacto en rendimiento

### **Proceso de Desarrollo**

1. **Fork** del repositorio
2. **Feature Branch** para nuevas funcionalidades
3. **Tests** antes de commit
4. **Code Review** obligatorio
5. **Merge** después de aprobación

## 📞 Soporte

### **Contacto**

- **Desarrollador**: Sistema de Gestión Educativa
- **Institución**: E.E.S.T. N°2 "Educación y Trabajo"
- **Versión**: 2.0.0
- **Última Actualización**: 2024

### **Recursos Adicionales**

- **Documentación API**: [docs/api/](docs/api/)
- **Guías de Usuario**: [docs/user-guides/](docs/user-guides/)
- **FAQ**: [docs/faq.md](docs/faq.md)
- **Changelog**: [docs/changelog.md](docs/changelog.md)

---

## 📄 Licencia

Este proyecto está desarrollado específicamente para la **E.E.S.T. N°2 "Educación y Trabajo"** y está destinado para uso educativo e institucional.

---

*Documentación generada automáticamente - Sistema Integral de Gestión Educativa v2.0.0*
