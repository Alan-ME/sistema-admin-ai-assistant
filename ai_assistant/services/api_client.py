"""
Cliente API para SistemaAdmin AI
Compatible con plan gratuito de Render
"""

import os
import requests
import logging
from typing import Dict, List, Any, Optional
import time
from datetime import datetime

logger = logging.getLogger(__name__)

class APIClient:
    """Cliente para comunicarse con la API REST de SistemaAdmin"""
    
    def __init__(self):
        self.base_url = os.getenv('API_BASE_URL', 'http://localhost/SistemaAdmin')
        self.api_key = os.getenv('API_KEY', 'sistema_admin_ai_2024_secure_key')
        self.timeout = 30  # 30 segundos timeout
        self.retry_attempts = 3
        self.retry_delay = 1  # 1 segundo entre reintentos
        
        # Cache simple para optimizar rendimiento
        self.cache = {}
        self.cache_ttl = 300  # 5 minutos
        
        logger.info(f"🔌 Cliente API inicializado - URL: {self.base_url}")
    
    def _make_request(self, endpoint: str, params: Dict[str, Any] = None) -> Dict[str, Any]:
        """Realizar petición HTTP a la API"""
        url = f"{self.base_url}/api_ia.php"
        
        # Parámetros por defecto
        request_params = {
            'api_key': self.api_key,
            'action': endpoint
        }
        
        # Agregar parámetros adicionales
        if params:
            request_params.update(params)
        
        # Verificar cache
        cache_key = f"{endpoint}_{hash(str(request_params))}"
        if self._is_cached(cache_key):
            logger.debug(f"📦 Datos obtenidos del cache: {endpoint}")
            return self.cache[cache_key]['data']
        
        # Realizar petición con reintentos
        for attempt in range(self.retry_attempts):
            try:
                logger.debug(f"🌐 Petición API: {endpoint} (intento {attempt + 1})")
                
                response = requests.get(
                    url,
                    params=request_params,
                    timeout=self.timeout,
                    headers={'User-Agent': 'SistemaAdmin-AI/1.0'}
                )
                
                # Verificar código de respuesta
                if response.status_code == 200:
                    data = response.json()
                    
                    if data.get('success', False):
                        # Guardar en cache
                        self._cache_data(cache_key, data)
                        return data
                    else:
                        raise Exception(f"Error de API: {data.get('error', 'Error desconocido')}")
                else:
                    raise Exception(f"HTTP {response.status_code}: {response.text}")
                    
            except requests.exceptions.Timeout:
                logger.warning(f"⏰ Timeout en petición {endpoint} (intento {attempt + 1})")
                if attempt < self.retry_attempts - 1:
                    time.sleep(self.retry_delay * (attempt + 1))
                else:
                    raise Exception(f"Timeout después de {self.retry_attempts} intentos")
                    
            except requests.exceptions.ConnectionError:
                logger.warning(f"🔌 Error de conexión en {endpoint} (intento {attempt + 1})")
                if attempt < self.retry_attempts - 1:
                    time.sleep(self.retry_delay * (attempt + 1))
                else:
                    raise Exception(f"Error de conexión después de {self.retry_attempts} intentos")
                    
            except Exception as e:
                logger.error(f"❌ Error en petición {endpoint}: {e}")
                if attempt < self.retry_attempts - 1:
                    time.sleep(self.retry_delay * (attempt + 1))
                else:
                    raise
        
        raise Exception(f"Error después de {self.retry_attempts} intentos")
    
    def _is_cached(self, cache_key: str) -> bool:
        """Verificar si los datos están en cache"""
        if cache_key in self.cache:
            cached_data = self.cache[cache_key]
            if time.time() - cached_data['timestamp'] < self.cache_ttl:
                return True
            else:
                # Cache expirado, remover
                del self.cache[cache_key]
        return False
    
    def _cache_data(self, cache_key: str, data: Dict[str, Any]):
        """Guardar datos en cache"""
        self.cache[cache_key] = {
            'data': data,
            'timestamp': time.time()
        }
        
        # Limpiar cache si tiene muchos elementos
        if len(self.cache) > 100:
            # Remover elementos más antiguos
            sorted_items = sorted(self.cache.items(), key=lambda x: x[1]['timestamp'])
            for key, _ in sorted_items[:20]:  # Remover 20 elementos más antiguos
                del self.cache[key]
    
    def get_health(self) -> Dict[str, Any]:
        """Verificar estado de la API"""
        try:
            return self._make_request('health')
        except Exception as e:
            logger.error(f"❌ Error verificando salud de API: {e}")
            return {
                'success': False,
                'status': 'unhealthy',
                'error': str(e)
            }
    
    def get_estadisticas(self) -> Dict[str, Any]:
        """Obtener estadísticas generales"""
        try:
            return self._make_request('estadisticas')
        except Exception as e:
            logger.error(f"❌ Error obteniendo estadísticas: {e}")
            return {'success': False, 'error': str(e)}
    
    def get_estudiantes(self) -> Dict[str, Any]:
        """Obtener lista de estudiantes"""
        try:
            return self._make_request('estudiantes')
        except Exception as e:
            logger.error(f"❌ Error obteniendo estudiantes: {e}")
            return {'success': False, 'error': str(e)}
    
    def get_estudiante(self, estudiante_id: int) -> Dict[str, Any]:
        """Obtener estudiante específico"""
        try:
            return self._make_request('estudiante', {'id': estudiante_id})
        except Exception as e:
            logger.error(f"❌ Error obteniendo estudiante {estudiante_id}: {e}")
            return {'success': False, 'error': str(e)}
    
    def get_estudiantes_curso(self, curso_id: int) -> Dict[str, Any]:
        """Obtener estudiantes por curso"""
        try:
            return self._make_request('estudiantes_curso', {'curso_id': curso_id})
        except Exception as e:
            logger.error(f"❌ Error obteniendo estudiantes del curso {curso_id}: {e}")
            return {'success': False, 'error': str(e)}
    
    def buscar_estudiantes(self, search_term: str) -> Dict[str, Any]:
        """Buscar estudiantes"""
        try:
            return self._make_request('buscar_estudiantes', {'search': search_term})
        except Exception as e:
            logger.error(f"❌ Error buscando estudiantes '{search_term}': {e}")
            return {'success': False, 'error': str(e)}
    
    def get_notas_estudiante(self, estudiante_id: int) -> Dict[str, Any]:
        """Obtener notas de estudiante"""
        try:
            return self._make_request('notas_estudiante', {'estudiante_id': estudiante_id})
        except Exception as e:
            logger.error(f"❌ Error obteniendo notas del estudiante {estudiante_id}: {e}")
            return {'success': False, 'error': str(e)}
    
    def get_profesores(self) -> Dict[str, Any]:
        """Obtener lista de profesores"""
        try:
            return self._make_request('profesores')
        except Exception as e:
            logger.error(f"❌ Error obteniendo profesores: {e}")
            return {'success': False, 'error': str(e)}
    
    def get_cursos(self) -> Dict[str, Any]:
        """Obtener lista de cursos"""
        try:
            return self._make_request('cursos')
        except Exception as e:
            logger.error(f"❌ Error obteniendo cursos: {e}")
            return {'success': False, 'error': str(e)}
    
    def get_materias(self) -> Dict[str, Any]:
        """Obtener lista de materias"""
        try:
            return self._make_request('materias')
        except Exception as e:
            logger.error(f"❌ Error obteniendo materias: {e}")
            return {'success': False, 'error': str(e)}
    
    def get_especialidades(self) -> Dict[str, Any]:
        """Obtener lista de especialidades"""
        try:
            return self._make_request('especialidades')
        except Exception as e:
            logger.error(f"❌ Error obteniendo especialidades: {e}")
            return {'success': False, 'error': str(e)}
    
    def get_llamados(self) -> Dict[str, Any]:
        """Obtener llamados de atención"""
        try:
            return self._make_request('llamados')
        except Exception as e:
            logger.error(f"❌ Error obteniendo llamados: {e}")
            return {'success': False, 'error': str(e)}
    
    def get_horarios(self) -> Dict[str, Any]:
        """Obtener horarios"""
        try:
            return self._make_request('horarios')
        except Exception as e:
            logger.error(f"❌ Error obteniendo horarios: {e}")
            return {'success': False, 'error': str(e)}
    
    def get_reporte_asistencia(self) -> Dict[str, Any]:
        """Obtener reporte de asistencia"""
        try:
            return self._make_request('reporte_asistencia')
        except Exception as e:
            logger.error(f"❌ Error obteniendo reporte de asistencia: {e}")
            return {'success': False, 'error': str(e)}
    
    def get_estadisticas_curso(self, curso_id: int) -> Dict[str, Any]:
        """Obtener estadísticas de curso"""
        try:
            return self._make_request('estadisticas_curso', {'curso_id': curso_id})
        except Exception as e:
            logger.error(f"❌ Error obteniendo estadísticas del curso {curso_id}: {e}")
            return {'success': False, 'error': str(e)}
    
    def execute_query(self, query_params: Dict[str, Any]) -> List[Dict[str, Any]]:
        """Ejecutar consulta basada en parámetros"""
        try:
            action = query_params.get('action', 'estudiantes')
            params = {k: v for k, v in query_params.items() if k != 'action'}
            
            result = self._make_request(action, params)
            
            if result.get('success', False):
                return result.get('data', [])
            else:
                logger.error(f"❌ Error en consulta API: {result.get('error', 'Error desconocido')}")
                return []
                
        except Exception as e:
            logger.error(f"❌ Error ejecutando consulta: {e}")
            return []
    
    def clear_cache(self):
        """Limpiar cache"""
        self.cache.clear()
        logger.info("🧹 Cache limpiado")
    
    def get_cache_stats(self) -> Dict[str, Any]:
        """Obtener estadísticas del cache"""
        return {
            'cache_size': len(self.cache),
            'cache_ttl': self.cache_ttl,
            'cached_keys': list(self.cache.keys())
        }
    
    def test_connection(self) -> bool:
        """Probar conexión con la API"""
        try:
            health = self.get_health()
            return health.get('success', False) and health.get('status') == 'healthy'
        except Exception as e:
            logger.error(f"❌ Error probando conexión: {e}")
            return False
