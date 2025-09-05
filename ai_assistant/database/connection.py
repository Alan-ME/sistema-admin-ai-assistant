"""
Conexión a base de datos MySQL para SistemaAdmin AI
Optimizado para plan gratuito de Render
"""

import os
import mysql.connector
from mysql.connector import Error, pooling
import logging
from typing import List, Dict, Any, Optional
import time

logger = logging.getLogger(__name__)

class DatabaseConnection:
    """Manejo de conexiones a MySQL con pool de conexiones"""
    
    def __init__(self):
        self.connection_pool = None
        self.config = self._get_config()
        self._create_connection_pool()
    
    def _get_config(self) -> dict:
        """Obtener configuración de base de datos desde variables de entorno"""
        return {
            'host': os.getenv('DB_HOST', 'localhost'),
            'user': os.getenv('DB_USER', 'root'),
            'password': os.getenv('DB_PASSWORD', ''),
            'database': os.getenv('DB_NAME', 'sistema_admin_eest2'),
            'port': int(os.getenv('DB_PORT', 3306)),
            'charset': 'utf8mb4',
            'use_unicode': True,
            'autocommit': True,
            'pool_name': 'sistema_admin_pool',
            'pool_size': 5,  # Optimizado para plan gratuito
            'pool_reset_session': True,
            'connect_timeout': 10,
            'read_timeout': 10,
            'write_timeout': 10
        }
    
    def _create_connection_pool(self):
        """Crear pool de conexiones a MySQL"""
        try:
            self.connection_pool = pooling.MySQLConnectionPool(**self.config)
            logger.info("✅ Pool de conexiones MySQL creado exitosamente")
        except Error as e:
            logger.error(f"❌ Error creando pool de conexiones: {e}")
            raise
    
    def get_connection(self):
        """Obtener conexión del pool"""
        try:
            return self.connection_pool.get_connection()
        except Error as e:
            logger.error(f"❌ Error obteniendo conexión: {e}")
            raise
    
    def execute_query(self, query: str, params: Optional[tuple] = None) -> List[Dict[str, Any]]:
        """Ejecutar consulta SQL y retornar resultados"""
        connection = None
        cursor = None
        
        try:
            connection = self.get_connection()
            cursor = connection.cursor(dictionary=True)
            
            # Ejecutar consulta
            cursor.execute(query, params or ())
            
            # Obtener resultados
            results = cursor.fetchall()
            
            logger.debug(f"✅ Consulta ejecutada: {query[:100]}...")
            return results
            
        except Error as e:
            logger.error(f"❌ Error ejecutando consulta: {e}")
            logger.error(f"Query: {query}")
            logger.error(f"Params: {params}")
            raise
        finally:
            if cursor:
                cursor.close()
            if connection:
                connection.close()
    
    def execute_insert(self, query: str, params: Optional[tuple] = None) -> int:
        """Ejecutar INSERT y retornar ID del registro insertado"""
        connection = None
        cursor = None
        
        try:
            connection = self.get_connection()
            cursor = connection.cursor()
            
            cursor.execute(query, params or ())
            connection.commit()
            
            return cursor.lastrowid
            
        except Error as e:
            logger.error(f"❌ Error ejecutando INSERT: {e}")
            raise
        finally:
            if cursor:
                cursor.close()
            if connection:
                connection.close()
    
    def execute_update(self, query: str, params: Optional[tuple] = None) -> int:
        """Ejecutar UPDATE y retornar número de filas afectadas"""
        connection = None
        cursor = None
        
        try:
            connection = self.get_connection()
            cursor = connection.cursor()
            
            cursor.execute(query, params or ())
            connection.commit()
            
            return cursor.rowcount
            
        except Error as e:
            logger.error(f"❌ Error ejecutando UPDATE: {e}")
            raise
        finally:
            if cursor:
                cursor.close()
            if connection:
                connection.close()
    
    def test_connection(self) -> bool:
        """Probar conexión a la base de datos"""
        try:
            test_query = "SELECT 1 as test"
            result = self.execute_query(test_query)
            return len(result) > 0 and result[0]['test'] == 1
        except Exception as e:
            logger.error(f"❌ Error probando conexión: {e}")
            return False
    
    def get_database_info(self) -> Dict[str, Any]:
        """Obtener información de la base de datos"""
        try:
            info = {}
            
            # Información de conexión
            info['host'] = self.config['host']
            info['database'] = self.config['database']
            info['port'] = self.config['port']
            
            # Estadísticas de tablas
            tables_query = """
            SELECT 
                table_name,
                table_rows,
                data_length,
                index_length
            FROM information_schema.tables 
            WHERE table_schema = %s
            ORDER BY table_rows DESC
            """
            
            tables = self.execute_query(tables_query, (self.config['database'],))
            info['tables'] = tables
            
            # Total de registros
            total_query = """
            SELECT 
                SUM(table_rows) as total_records
            FROM information_schema.tables 
            WHERE table_schema = %s
            """
            
            total = self.execute_query(total_query, (self.config['database'],))
            info['total_records'] = total[0]['total_records'] if total else 0
            
            return info
            
        except Exception as e:
            logger.error(f"❌ Error obteniendo información de BD: {e}")
            return {}
    
    def close(self):
        """Cerrar pool de conexiones"""
        if self.connection_pool:
            # El pool se cierra automáticamente al finalizar la aplicación
            logger.info("🔌 Pool de conexiones cerrado")
    
    def __enter__(self):
        return self
    
    def __exit__(self, exc_type, exc_val, exc_tb):
        self.close()
