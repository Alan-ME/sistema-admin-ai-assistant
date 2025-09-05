# 📊 Diagramas UML del Sistema Integral de Gestión Educativa (SIGE)

Este directorio contiene los diagramas UML en PlantUML que documentan la arquitectura actual y propuesta del sistema SIGE.

## 📋 Lista de Diagramas

### 1. **Arquitectura Actual** (`01_arquitectura_actual.puml`)
- **Propósito**: Muestra la estructura actual del sistema
- **Problemas identificados**: Alto acoplamiento, falta de separación de responsabilidades
- **Uso**: Análisis de deuda técnica

### 2. **Arquitectura Propuesta** (`02_arquitectura_propuesta.puml`)
- **Propósito**: Arquitectura objetivo post-refactoring
- **Beneficios**: Bajo acoplamiento, separación clara de capas
- **Uso**: Guía de implementación

### 3. **Secuencia - Cargar Nota** (`03_secuencia_cargar_nota.puml`)
- **Propósito**: Comparación entre flujo actual vs propuesto
- **Enfoque**: Demuestra mejora en manejo de errores y separación
- **Uso**: Validación de diseño

### 4. **Modelos (TDCs)** (`04_modelos_tdc.puml`)
- **Propósito**: Tipos de Datos Concretos del sistema
- **Características**: Validaciones, inmutabilidad, métodos de negocio
- **Uso**: Implementación de entidades

### 5. **Interfaces (TDAs)** (`05_interfaces_tda.puml`)
- **Propósito**: Tipos de Datos Abstractos (contratos)
- **Beneficios**: Múltiples implementaciones, testing fácil
- **Uso**: Definición de servicios

### 6. **Componentes General** (`06_componentes_general.puml`)
- **Propósito**: Visión general de componentes del sistema
- **Enfoque**: Arquitectura de microservicios
- **Uso**: Documentación técnica

### 7. **Despliegue** (`07_despliegue.puml`)
- **Propósito**: Arquitectura de despliegue en producción
- **Incluye**: Servidores, configuración, requisitos
- **Uso**: Planificación de infraestructura

### 8. **Casos de Uso** (`08_casos_uso.puml`)
- **Propósito**: Funcionalidades del sistema por rol
- **Actores**: Admin, Directivo, Profesor, Preceptor, Secretario
- **Uso**: Documentación de requisitos

## 🛠️ Cómo Usar los Diagramas

### **Requisitos:**
- Editor que soporte PlantUML (VS Code, IntelliJ, etc.)
- Extensión PlantUML instalada

### **Comandos útiles:**
```bash
# Generar imagen PNG
plantuml -tpng *.puml

# Generar imagen SVG
plantuml -tsvg *.puml

# Generar todas las imágenes
plantuml -tpng diagrams/*.puml
```

### **Editores recomendados:**
- **VS Code**: Extensión "PlantUML"
- **IntelliJ IDEA**: Plugin "PlantUML integration"
- **Online**: [PlantUML Online Server](http://www.plantuml.com/plantuml/uml/)

## 🎯 Objetivos de los Diagramas

### **Para el Desarrollo:**
- Guía de refactoring
- Documentación de arquitectura
- Validación de diseño

### **Para el Cliente:**
- Demostración de profesionalismo
- Justificación de inversión
- Documentación técnica

### **Para el Equipo:**
- Comprensión del sistema
- Onboarding de nuevos desarrolladores
- Mantenimiento futuro

## 📈 Evolución de los Diagramas

### **Fase 1: Análisis (Actual)**
- ✅ Diagramas 1, 3, 8 (estado actual)
- ✅ Identificación de problemas

### **Fase 2: Diseño (En progreso)**
- ✅ Diagramas 2, 4, 5, 6 (arquitectura propuesta)
- ✅ Definición de interfaces y modelos

### **Fase 3: Implementación (Futuro)**
- 🔄 Diagrama 7 (despliegue)
- 🔄 Actualización basada en implementación real

## 🔗 Relación con el Documento de Análisis

Estos diagramas complementan el documento de análisis teórico:

- **Diagramas 1-3**: Corresponde a la sección "Modularidad"
- **Diagramas 4-5**: Corresponde a "TDA vs TDC"
- **Diagrama 6**: Corresponde a "Gestión de Complejidad"
- **Diagramas 7-8**: Corresponde a "Objetivos de Calidad"

## 📝 Notas de Mantenimiento

- **Actualizar** los diagramas cuando cambie la arquitectura
- **Versionar** los cambios importantes
- **Documentar** las decisiones de diseño
- **Validar** con el equipo antes de implementar

---

**Creado por**: Equipo de Desarrollo SIGE  
**Fecha**: 2024  
**Versión**: 1.0  
**Estado**: En desarrollo
