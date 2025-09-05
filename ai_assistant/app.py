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
from services.api_client import APIClient
from services.query_processor import QueryProcessor
from services.data_analyzer import DataAnalyzer
from services.nlp_service import NLPService

# Configurar logging
import os
os.makedirs('logs', exist_ok=True)

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
api_client = None
query_processor = None
data_analyzer = None
nlp_service = None

@app.on_event("startup")
async def startup_event():
    """Inicializar servicios al arrancar la aplicación"""
    global api_client, query_processor, data_analyzer, nlp_service
    
    try:
        # Crear directorio de logs si no existe
        os.makedirs("logs", exist_ok=True)
        
        # Inicializar cliente API
        api_client = APIClient()
        logger.info("✅ Cliente API inicializado")
        
        # Inicializar servicios
        query_processor = QueryProcessor(api_client)
        data_analyzer = DataAnalyzer(api_client)
        nlp_service = NLPService()
        
        logger.info("✅ Servicios de IA inicializados correctamente")
        
    except Exception as e:
        logger.error(f"❌ Error al inicializar servicios: {e}")
        raise

@app.on_event("shutdown")
async def shutdown_event():
    """Cerrar conexiones al apagar la aplicación"""
    logger.info("🔌 Aplicación cerrada")

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
        
        # Generar consulta API
        api_query = query_processor.generate_api_query(intent, entities)
        
        # Ejecutar consulta API
        results = api_client.execute_query(api_query)
        
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
        # Verificar conexión a API
        health_data = api_client.get_health()
        
        return JSONResponse({
            "status": "healthy",
            "api": "connected",
            "total_students": health_data.get('total_estudiantes', 0),
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
        stats = api_client.get_estadisticas()
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
        if search:
            students = api_client.buscar_estudiantes(search)
        elif course_id:
            students = api_client.get_estudiantes_curso(course_id)
        else:
            students = api_client.get_estudiantes()
        
        return JSONResponse({
            "success": True,
            "students": students.get('data', []),
            "count": students.get('count', 0)
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
        courses = api_client.get_cursos()
        return JSONResponse({
            "success": True,
            "courses": courses.get('data', [])
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

