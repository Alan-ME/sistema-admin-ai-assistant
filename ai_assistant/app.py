#!/usr/bin/env python3
"""
Sistema de Inteligencia Artificial para SistemaAdmin
Conexión directa a MySQL para consultas en tiempo real
"""

import os
import uvicorn
from fastapi import FastAPI, Request, Form, HTTPException
from fastapi.responses import HTMLResponse, JSONResponse
from fastapi.staticfiles import StaticFiles
from fastapi.templating import Jinja2Templates
import logging
from datetime import datetime
import asyncio
import threading
import time

# Importar servicios locales
from database.connection import DatabaseConnection
from services.query_processor import QueryProcessor
from services.data_analyzer import DataAnalyzer
from services.real_time_monitor import RealTimeMonitor
from services.nlp_service import NLPService

# Configurar logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler('logs/ai_system.log'),
        logging.StreamHandler()
    ]
)

logger = logging.getLogger(__name__)

# Crear aplicación FastAPI
app = FastAPI(
    title="SistemaAdmin AI Assistant",
    description="Sistema de IA para consultar datos educativos en tiempo real",
    version="1.0.0"
)

# Montar archivos estáticos
app.mount("/static", StaticFiles(directory="static"), name="static")

# Configurar templates
templates = Jinja2Templates(directory="templates")

# Inicializar servicios
db_connection = None
query_processor = None
data_analyzer = None
nlp_service = None
monitor = None

@app.on_event("startup")
async def startup_event():
    """Inicializar servicios al arrancar la aplicación"""
    global db_connection, query_processor, data_analyzer, nlp_service, monitor
    
    try:
        # Crear directorio de logs si no existe
        os.makedirs("logs", exist_ok=True)
        
        # Inicializar conexión a base de datos
        db_connection = DatabaseConnection()
        logger.info("✅ Conexión a base de datos establecida")
        
        # Inicializar servicios
        query_processor = QueryProcessor(db_connection)
        data_analyzer = DataAnalyzer(db_connection)
        nlp_service = NLPService()
        monitor = RealTimeMonitor(db_connection)
        
        # Iniciar monitoreo en tiempo real
        monitor.start_monitoring()
        logger.info("✅ Servicios de IA inicializados correctamente")
        
    except Exception as e:
        logger.error(f"❌ Error al inicializar servicios: {e}")
        raise

@app.on_event("shutdown")
async def shutdown_event():
    """Cerrar conexiones al apagar la aplicación"""
    if db_connection:
        db_connection.close()
    if monitor:
        monitor.stop_monitoring()
    logger.info("🔌 Conexiones cerradas")

@app.get("/", response_class=HTMLResponse)
async def home(request: Request):
    """Página principal con interfaz de chat"""
    return templates.TemplateResponse("index.html", {
        "request": request,
        "timestamp": datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    })

@app.post("/api/query")
async def process_query(request: Request, question: str = Form(...)):
    """Procesar consulta del usuario"""
    try:
        logger.info(f"🤖 Procesando consulta: {question}")
        
        # Procesar la pregunta con NLP
        intent, entities = nlp_service.process_question(question)
        
        # Generar consulta SQL
        sql_query = query_processor.generate_sql_query(intent, entities)
        
        # Ejecutar consulta
        results = db_connection.execute_query(sql_query)
        
        # Analizar resultados
        analysis = data_analyzer.analyze_results(results, intent)
        
        # Generar respuesta natural
        response = nlp_service.generate_response(results, analysis, intent)
        
        return JSONResponse({
            "success": True,
            "question": question,
            "response": response,
            "data": results,
            "analysis": analysis,
            "timestamp": datetime.now().isoformat()
        })
        
    except Exception as e:
        logger.error(f"❌ Error procesando consulta: {e}")
        return JSONResponse({
            "success": False,
            "error": f"Error procesando consulta: {str(e)}",
            "timestamp": datetime.now().isoformat()
        })

@app.get("/api/health")
async def health_check():
    """Verificar estado del sistema"""
    try:
        # Verificar conexión a base de datos
        test_query = "SELECT COUNT(*) as total FROM estudiantes"
        result = db_connection.execute_query(test_query)
        
        return JSONResponse({
            "status": "healthy",
            "database": "connected",
            "total_students": result[0]['total'] if result else 0,
            "timestamp": datetime.now().isoformat()
        })
    except Exception as e:
        return JSONResponse({
            "status": "unhealthy",
            "error": str(e),
            "timestamp": datetime.now().isoformat()
        })

@app.get("/api/stats")
async def get_system_stats():
    """Obtener estadísticas del sistema"""
    try:
        stats = data_analyzer.get_system_statistics()
        return JSONResponse({
            "success": True,
            "stats": stats,
            "timestamp": datetime.now().isoformat()
        })
    except Exception as e:
        return JSONResponse({
            "success": False,
            "error": str(e),
            "timestamp": datetime.now().isoformat()
        })

@app.get("/api/students")
async def get_students(course_id: int = None, search: str = None):
    """Obtener lista de estudiantes"""
    try:
        query = "SELECT * FROM estudiantes WHERE activo = 1"
        params = []
        
        if course_id:
            query += " AND curso_id = %s"
            params.append(course_id)
            
        if search:
            query += " AND (nombre LIKE %s OR apellido LIKE %s)"
            params.extend([f"%{search}%", f"%{search}%"])
            
        query += " ORDER BY apellido, nombre LIMIT 100"
        
        students = db_connection.execute_query(query, params)
        return JSONResponse({
            "success": True,
            "students": students,
            "count": len(students)
        })
    except Exception as e:
        return JSONResponse({
            "success": False,
            "error": str(e)
        })

@app.get("/api/courses")
async def get_courses():
    """Obtener lista de cursos"""
    try:
        query = """
        SELECT c.*, e.nombre as especialidad_nombre, t.nombre as turno_nombre
        FROM cursos c
        LEFT JOIN especialidades e ON c.especialidad_id = e.id
        LEFT JOIN turnos t ON c.turno_id = t.id
        WHERE c.activo = 1
        ORDER BY c.anio, c.division
        """
        courses = db_connection.execute_query(query)
        return JSONResponse({
            "success": True,
            "courses": courses
        })
    except Exception as e:
        return JSONResponse({
            "success": False,
            "error": str(e)
        })

if __name__ == "__main__":
    # Configuración para desarrollo local
    port = int(os.getenv("PORT", 8000))
    host = os.getenv("HOST", "0.0.0.0")
    
    logger.info(f"🚀 Iniciando servidor en {host}:{port}")
    
    uvicorn.run(
        "app:app",
        host=host,
        port=port,
        reload=os.getenv("DEBUG", "False").lower() == "true",
        log_level="info"
    )
