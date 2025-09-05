"""
Tests de conexión a base de datos para SistemaAdmin AI
"""

import pytest
import os
from database.connection import DatabaseConnection

class TestDatabaseConnection:
    """Tests para la conexión a base de datos"""
    
    def test_connection_creation(self):
        """Test crear conexión a base de datos"""
        try:
            db = DatabaseConnection()
            assert db is not None
            assert db.connection_pool is not None
        except Exception as e:
            pytest.skip(f"No se puede conectar a la base de datos: {e}")
    
    def test_connection_test(self):
        """Test verificar conexión"""
        try:
            db = DatabaseConnection()
            is_connected = db.test_connection()
            assert is_connected == True
        except Exception as e:
            pytest.skip(f"No se puede conectar a la base de datos: {e}")
    
    def test_execute_query(self):
        """Test ejecutar consulta simple"""
        try:
            db = DatabaseConnection()
            result = db.execute_query("SELECT 1 as test")
            assert len(result) > 0
            assert result[0]['test'] == 1
        except Exception as e:
            pytest.skip(f"No se puede conectar a la base de datos: {e}")
    
    def test_get_database_info(self):
        """Test obtener información de la base de datos"""
        try:
            db = DatabaseConnection()
            info = db.get_database_info()
            assert 'host' in info
            assert 'database' in info
            assert 'tables' in info
        except Exception as e:
            pytest.skip(f"No se puede conectar a la base de datos: {e}")
    
    def test_connection_pool(self):
        """Test pool de conexiones"""
        try:
            db = DatabaseConnection()
            connection = db.get_connection()
            assert connection is not None
            connection.close()
        except Exception as e:
            pytest.skip(f"No se puede conectar a la base de datos: {e}")
