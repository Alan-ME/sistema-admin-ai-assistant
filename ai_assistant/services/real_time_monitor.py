"""
Monitor en tiempo real para SistemaAdmin AI
Detecta cambios en la base de datos y actualiza cache
"""

import logging
import threading
import time
from datetime import datetime, timedelta
from typing import Dict, Any, Optional

logger = logging.getLogger(__name__)

class RealTimeMonitor:
    """Monitor que detecta cambios en la base de datos en tiempo real"""
    
    def __init__(self, db_connection):
        self.db = db_connection
        self.running = False
        self.monitor_thread = None
        self.last_check = None
        self.cache = {}
        self.change_callbacks = []
        
        # Configuración de monitoreo
        self.check_interval = 30  # segundos (optimizado para plan gratuito)
        self.cache_ttl = 300  # 5 minutos
        
    def start_monitoring(self):
        """Iniciar monitoreo en tiempo real"""
        if self.running:
            logger.warning("⚠️ El monitor ya está ejecutándose")
            return
        
        self.running = True
        self.last_check = datetime.now() - timedelta(minutes=1)
        
        self.monitor_thread = threading.Thread(target=self._monitor_loop, daemon=True)
        self.monitor_thread.start()
        
        logger.info("🔄 Monitor en tiempo real iniciado")
    
    def stop_monitoring(self):
        """Detener monitoreo"""
        self.running = False
        if self.monitor_thread:
            self.monitor_thread.join(timeout=5)
        
        logger.info("⏹️ Monitor en tiempo real detenido")
    
    def _monitor_loop(self):
        """Loop principal del monitor"""
        while self.running:
            try:
                self._check_for_changes()
                time.sleep(self.check_interval)
            except Exception as e:
                logger.error(f"❌ Error en loop de monitoreo: {e}")
                time.sleep(60)  # Esperar más tiempo en caso de error
    
    def _check_for_changes(self):
        """Verificar cambios en la base de datos"""
        try:
            current_time = datetime.now()
            
            # Verificar cambios en diferentes tablas
            changes_detected = False
            
            # Cambios en estudiantes
            if self._check_student_changes():
                changes_detected = True
                logger.info("👥 Cambios detectados en estudiantes")
            
            # Cambios en notas
            if self._check_grade_changes():
                changes_detected = True
                logger.info("📊 Cambios detectados en notas")
            
            # Cambios en llamados de atención
            if self._check_attention_call_changes():
                changes_detected = True
                logger.info("⚠️ Cambios detectados en llamados de atención")
            
            # Cambios en profesores
            if self._check_teacher_changes():
                changes_detected = True
                logger.info("👨‍🏫 Cambios detectados en profesores")
            
            if changes_detected:
                self._notify_changes()
                self._update_cache()
            
            self.last_check = current_time
            
        except Exception as e:
            logger.error(f"❌ Error verificando cambios: {e}")
    
    def _check_student_changes(self) -> bool:
        """Verificar cambios en la tabla de estudiantes"""
        try:
            query = """
            SELECT COUNT(*) as count
            FROM estudiantes 
            WHERE fecha_ingreso >= %s OR 
                  (fecha_ingreso IS NULL AND activo = 1)
            """
            
            result = self.db.execute_query(query, (self.last_check,))
            return result and result[0]['count'] > 0
            
        except Exception as e:
            logger.error(f"❌ Error verificando cambios en estudiantes: {e}")
            return False
    
    def _check_grade_changes(self) -> bool:
        """Verificar cambios en la tabla de notas"""
        try:
            query = """
            SELECT COUNT(*) as count
            FROM notas 
            WHERE fecha_registro >= %s
            """
            
            result = self.db.execute_query(query, (self.last_check,))
            return result and result[0]['count'] > 0
            
        except Exception as e:
            logger.error(f"❌ Error verificando cambios en notas: {e}")
            return False
    
    def _check_attention_call_changes(self) -> bool:
        """Verificar cambios en llamados de atención"""
        try:
            query = """
            SELECT COUNT(*) as count
            FROM llamados_atencion 
            WHERE fecha_registro >= %s
            """
            
            result = self.db.execute_query(query, (self.last_check,))
            return result and result[0]['count'] > 0
            
        except Exception as e:
            logger.error(f"❌ Error verificando cambios en llamados: {e}")
            return False
    
    def _check_teacher_changes(self) -> bool:
        """Verificar cambios en profesores"""
        try:
            query = """
            SELECT COUNT(*) as count
            FROM profesores 
            WHERE fecha_ingreso >= %s OR 
                  (fecha_ingreso IS NULL AND activo = 1)
            """
            
            result = self.db.execute_query(query, (self.last_check,))
            return result and result[0]['count'] > 0
            
        except Exception as e:
            logger.error(f"❌ Error verificando cambios en profesores: {e}")
            return False
    
    def _notify_changes(self):
        """Notificar cambios detectados"""
        for callback in self.change_callbacks:
            try:
                callback()
            except Exception as e:
                logger.error(f"❌ Error en callback de cambios: {e}")
    
    def _update_cache(self):
        """Actualizar cache con datos recientes"""
        try:
            # Actualizar estadísticas generales
            self.cache['system_stats'] = self._get_system_stats()
            self.cache['last_update'] = datetime.now()
            
            logger.debug("💾 Cache actualizado")
            
        except Exception as e:
            logger.error(f"❌ Error actualizando cache: {e}")
    
    def _get_system_stats(self) -> Dict[str, Any]:
        """Obtener estadísticas actuales del sistema"""
        try:
            query = """
            SELECT 
                (SELECT COUNT(*) FROM estudiantes WHERE activo = 1) as estudiantes,
                (SELECT COUNT(*) FROM profesores WHERE activo = 1) as profesores,
                (SELECT COUNT(*) FROM cursos WHERE activo = 1) as cursos,
                (SELECT COUNT(*) FROM notas) as notas,
                (SELECT COUNT(*) FROM llamados_atencion) as llamados
            """
            
            result = self.db.execute_query(query)
            if result:
                stats = result[0]
                stats['timestamp'] = datetime.now().isoformat()
                return stats
            
            return {}
            
        except Exception as e:
            logger.error(f"❌ Error obteniendo estadísticas: {e}")
            return {}
    
    def get_cached_data(self, key: str) -> Optional[Any]:
        """Obtener datos del cache"""
        if key in self.cache:
            data = self.cache[key]
            last_update = data.get('last_update', datetime.min)
            
            # Verificar si el cache no ha expirado
            if datetime.now() - last_update < timedelta(seconds=self.cache_ttl):
                return data
        
        return None
    
    def set_cached_data(self, key: str, data: Any):
        """Almacenar datos en cache"""
        self.cache[key] = {
            'data': data,
            'last_update': datetime.now()
        }
    
    def add_change_callback(self, callback):
        """Agregar callback para notificar cambios"""
        self.change_callbacks.append(callback)
    
    def remove_change_callback(self, callback):
        """Remover callback de notificaciones"""
        if callback in self.change_callbacks:
            self.change_callbacks.remove(callback)
    
    def get_monitoring_status(self) -> Dict[str, Any]:
        """Obtener estado del monitor"""
        return {
            "running": self.running,
            "last_check": self.last_check.isoformat() if self.last_check else None,
            "check_interval": self.check_interval,
            "cache_size": len(self.cache),
            "callbacks_count": len(self.change_callbacks)
        }
    
    def force_cache_update(self):
        """Forzar actualización del cache"""
        logger.info("🔄 Forzando actualización del cache")
        self._update_cache()
    
    def get_recent_changes(self, hours: int = 1) -> Dict[str, Any]:
        """Obtener cambios recientes en las últimas N horas"""
        try:
            since = datetime.now() - timedelta(hours=hours)
            
            changes = {
                "estudiantes": 0,
                "notas": 0,
                "llamados": 0,
                "profesores": 0,
                "timestamp": datetime.now().isoformat()
            }
            
            # Contar cambios en estudiantes
            student_query = """
            SELECT COUNT(*) as count
            FROM estudiantes 
            WHERE fecha_ingreso >= %s
            """
            result = self.db.execute_query(student_query, (since,))
            if result:
                changes["estudiantes"] = result[0]['count']
            
            # Contar cambios en notas
            grade_query = """
            SELECT COUNT(*) as count
            FROM notas 
            WHERE fecha_registro >= %s
            """
            result = self.db.execute_query(grade_query, (since,))
            if result:
                changes["notas"] = result[0]['count']
            
            # Contar cambios en llamados
            call_query = """
            SELECT COUNT(*) as count
            FROM llamados_atencion 
            WHERE fecha_registro >= %s
            """
            result = self.db.execute_query(call_query, (since,))
            if result:
                changes["llamados"] = result[0]['count']
            
            # Contar cambios en profesores
            teacher_query = """
            SELECT COUNT(*) as count
            FROM profesores 
            WHERE fecha_ingreso >= %s
            """
            result = self.db.execute_query(teacher_query, (since,))
            if result:
                changes["profesores"] = result[0]['count']
            
            return changes
            
        except Exception as e:
            logger.error(f"❌ Error obteniendo cambios recientes: {e}")
            return {"error": str(e)}
    
    def cleanup_old_cache(self):
        """Limpiar cache antiguo"""
        try:
            current_time = datetime.now()
            keys_to_remove = []
            
            for key, data in self.cache.items():
                if isinstance(data, dict) and 'last_update' in data:
                    last_update = data['last_update']
                    if current_time - last_update > timedelta(seconds=self.cache_ttl * 2):
                        keys_to_remove.append(key)
            
            for key in keys_to_remove:
                del self.cache[key]
            
            if keys_to_remove:
                logger.info(f"🧹 Cache limpiado: {len(keys_to_remove)} entradas removidas")
                
        except Exception as e:
            logger.error(f"❌ Error limpiando cache: {e}")
