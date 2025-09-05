"""
Tests de cliente API para SistemaAdmin AI
"""

import pytest
from unittest.mock import Mock, patch
from services.api_client import APIClient

class TestAPIClient:
    """Tests para el cliente API"""
    
    def test_api_client_creation(self):
        """Test crear cliente API"""
        with patch.dict('os.environ', {
            'API_BASE_URL': 'http://localhost/test',
            'API_KEY': 'test_key'
        }):
            client = APIClient()
            assert client is not None
            assert client.base_url == 'http://localhost/test'
            assert client.api_key == 'test_key'
    
    def test_validate_query(self):
        """Test validación de consultas"""
        with patch.dict('os.environ', {
            'API_BASE_URL': 'http://localhost/test',
            'API_KEY': 'test_key'
        }):
            client = APIClient()
            
            # Consulta válida
            valid_query = {'action': 'estudiantes'}
            assert client.validate_query(valid_query) == True
            
            # Consulta inválida
            invalid_query = {'action': 'invalid_action'}
            assert client.validate_query(invalid_query) == False
    
    def test_optimize_query(self):
        """Test optimización de consultas"""
        with patch.dict('os.environ', {
            'API_BASE_URL': 'http://localhost/test',
            'API_KEY': 'test_key'
        }):
            client = APIClient()
            
            # Consulta que necesita límite
            query = {'action': 'estudiantes'}
            optimized = client.optimize_query(query)
            assert 'limit' in optimized
            assert optimized['limit'] == 50
