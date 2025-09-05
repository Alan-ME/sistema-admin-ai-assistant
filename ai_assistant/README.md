# 🤖 SistemaAdmin AI Assistant

Sistema de Inteligencia Artificial para consultar datos del SistemaAdmin en tiempo real mediante conexión directa a MySQL.

## 🚀 Características

- ✅ **Conexión en Tiempo Real** a MySQL de XAMPP
- ✅ **Procesamiento de Lenguaje Natural** en español
- ✅ **Interfaz Web Amigable** con chat conversacional
- ✅ **Análisis Inteligente** de datos educativos
- ✅ **Hosting Gratuito** en Render
- ✅ **Sincronización Instantánea** de cambios

## 📋 Prerrequisitos

- Python 3.8+
- MySQL (XAMPP)
- Cuenta en Render (gratuita)
- Acceso a la base de datos `sistema_admin_eest2`

## 🛠️ Instalación

### 1. Clonar el Repositorio

```bash
git clone https://github.com/tu-usuario/sistema-admin-ai-assistant.git
cd sistema-admin-ai-assistant
```

### 2. Instalar Dependencias

```bash
pip install -r requirements.txt
```

### 3. Configurar Variables de Entorno

Copiar el archivo de ejemplo y configurar:

```bash
cp env.example .env
```

Editar `.env` con tus datos:

```env
# Configuración de Base de Datos MySQL
DB_HOST=tu_ip_publica_o_localhost
DB_USER=ia_user
DB_PASSWORD=password_seguro_ia
DB_NAME=sistema_admin_eest2
DB_PORT=3306

# Configuración de IA
OPENAI_API_KEY=tu_api_key_openai
# O usar Hugging Face (gratuito)
HUGGINGFACE_API_KEY=tu_api_key_huggingface

# Configuración del Servidor
HOST=0.0.0.0
PORT=8000
DEBUG=false
```

### 4. Configurar MySQL para Conexiones Externas

#### En XAMPP:

1. **Editar `my.cnf`**:
```ini
[mysqld]
bind-address = 0.0.0.0
port = 3306
```

2. **Crear usuario para IA**:
```sql
CREATE USER 'ia_user'@'%' IDENTIFIED BY 'password_seguro_ia';
GRANT SELECT ON sistema_admin_eest2.* TO 'ia_user'@'%';
FLUSH PRIVILEGES;
```

3. **Configurar Firewall**:
   - Abrir puerto 3306
   - Configurar reglas de seguridad

### 5. Ejecutar la Aplicación

```bash
python app.py
```

La aplicación estará disponible en: `http://localhost:8000`

## 🌐 Despliegue en Render

### 1. Preparar el Repositorio

```bash
# Subir a GitHub
git add .
git commit -m "Sistema de IA con conexión directa"
git push origin main
```

### 2. Crear Servicio en Render

1. **Ir a Render Dashboard**
2. **Crear nuevo Web Service**
3. **Conectar repositorio de GitHub**
4. **Configurar variables de entorno**:
   ```
   DB_HOST=tu_ip_publica
   DB_USER=ia_user
   DB_PASSWORD=password_seguro_ia
   DB_NAME=sistema_admin_eest2
   DB_PORT=3306
   OPENAI_API_KEY=tu_api_key
   ```

### 3. Configuración de Render

- **Build Command**: `pip install -r requirements.txt`
- **Start Command**: `python app.py`
- **Plan**: Free
- **Region**: Oregon (recomendado)

## 💬 Uso del Sistema

### Interfaz Web

Acceder a la interfaz web donde puedes:

- **Hacer preguntas en español**: "¿Cuántos estudiantes hay en 1° año A?"
- **Consultar datos específicos**: "Muéstrame las notas de Juan Pérez"
- **Generar reportes**: "¿Cuál es el promedio de notas por materia?"
- **Analizar patrones**: "¿Qué estudiantes tienen más inasistencias?"

### Ejemplos de Consultas

#### Consultas Básicas:
- "¿Cuántos estudiantes hay en total?"
- "¿Cuáles son las especialidades disponibles?"
- "Muéstrame los profesores de Matemática"

#### Consultas Avanzadas:
- "¿Cuál es el rendimiento promedio por curso?"
- "¿Qué estudiantes tienen notas bajas en Matemática?"
- "¿Cuántos llamados de atención hubo esta semana?"
- "¿Cuál es la distribución de estudiantes por turno?"

#### Análisis Estadísticos:
- "Genera un reporte de asistencia por mes"
- "¿Cuáles son las materias con mejor rendimiento?"
- "Analiza el progreso de los estudiantes de Informática"

## 🔧 Configuración Avanzada

### Variables de Entorno

| Variable | Descripción | Valor por Defecto |
|----------|-------------|-------------------|
| `DB_HOST` | Host de MySQL | localhost |
| `DB_USER` | Usuario de MySQL | ia_user |
| `DB_PASSWORD` | Contraseña de MySQL | - |
| `DB_NAME` | Nombre de la base de datos | sistema_admin_eest2 |
| `DB_PORT` | Puerto de MySQL | 3306 |
| `OPENAI_API_KEY` | API Key de OpenAI | - |
| `HUGGINGFACE_API_KEY` | API Key de Hugging Face | - |
| `HOST` | Host del servidor | 0.0.0.0 |
| `PORT` | Puerto del servidor | 8000 |
| `DEBUG` | Modo debug | false |

### Configuración de Seguridad

- **Usuario dedicado**: Solo permisos de lectura
- **Conexión segura**: SSL/TLS cuando sea posible
- **Validación de consultas**: Prevención de SQL injection
- **Rate limiting**: Límite de consultas por minuto

## 📊 Monitoreo

### Logs del Sistema

Los logs se guardan en `logs/ai_system.log`:

```bash
tail -f logs/ai_system.log
```

### Métricas Disponibles

- Número de consultas por hora
- Tiempo de respuesta promedio
- Errores de conexión
- Cambios detectados en BD
- Uso de memoria y CPU

### Health Check

Verificar estado del sistema:

```bash
curl http://localhost:8000/api/health
```

## 🧪 Testing

### Ejecutar Tests

```bash
# Tests de conexión
python -m pytest tests/test_connection.py

# Tests de consultas
python -m pytest tests/test_queries.py

# Tests completos
python -m pytest tests/
```

## 🛠️ Solución de Problemas

### Error de Conexión a MySQL

```bash
# Verificar conectividad
telnet tu_ip_publica 3306

# Verificar credenciales
mysql -h tu_ip_publica -u ia_user -p
```

### Error de Consulta

```bash
# Verificar logs
tail -f logs/ai_system.log

# Test de consulta manual
python -c "from database.connection import DatabaseConnection; db = DatabaseConnection(); print(db.execute_query('SELECT COUNT(*) FROM estudiantes'))"
```

### Error en Render

1. **Verificar logs** en Render Dashboard
2. **Verificar variables de entorno**
3. **Verificar conectividad** a MySQL
4. **Verificar puerto** 3306 abierto

## 📈 Rendimiento

### Optimizaciones para Plan Gratuito

- **Pool de conexiones**: 5 conexiones máximo
- **Cache inteligente**: TTL de 5 minutos
- **Consultas optimizadas**: LIMIT automático
- **Monitoreo eficiente**: Verificación cada 30 segundos

### Límites del Plan Gratuito

- **750 horas/mes** de ejecución
- **512 MB RAM** máximo
- **Sin persistencia** de disco
- **Sleep** después de 15 minutos de inactividad

## 🤝 Contribuir

1. Fork el proyecto
2. Crear rama para feature (`git checkout -b feature/nueva-funcionalidad`)
3. Commit cambios (`git commit -m 'Agregar nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Crear Pull Request

## 📄 Licencia

Este proyecto está bajo la Licencia MIT. Ver el archivo `LICENSE` para más detalles.

## 📞 Soporte

- **Email**: soporte@tu-dominio.com
- **GitHub Issues**: [Crear issue](https://github.com/tu-usuario/sistema-admin-ai-assistant/issues)
- **Documentación**: [Wiki del proyecto](https://github.com/tu-usuario/sistema-admin-ai-assistant/wiki)

---

**¡Tu sistema de IA está listo para analizar datos en tiempo real! 🚀**
