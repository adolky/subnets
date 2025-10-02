#!/bin/bash
# Subnet Calculator - Quick Deployment Script
# This script deploys the subnet calculator using Docker

set -e

echo "🌐 Subnet Calculator - Docker Deployment"
echo "========================================"

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    echo "❌ Docker is not installed. Please install Docker first."
    echo "   Visit: https://docs.docker.com/get-docker/"
    exit 1
fi

# Check if Docker Compose is installed
if ! command -v docker-compose &> /dev/null; then
    echo "❌ Docker Compose is not installed. Please install Docker Compose first."
    echo "   Visit: https://docs.docker.com/compose/install/"
    exit 1
fi

echo "✅ Docker and Docker Compose are available"

# Function to deploy
deploy() {
    local mode=$1
    
    if [ "$mode" = "prod" ] || [ "$mode" = "production" ]; then
        echo "🚀 Deploying in PRODUCTION mode..."
        docker-compose -f docker-compose.prod.yml down --remove-orphans
        docker-compose -f docker-compose.prod.yml up -d --build
        
        echo "✅ Production deployment complete!"
        echo "🌐 Access your application at: http://localhost"
        echo "📊 Monitor with: docker-compose -f docker-compose.prod.yml logs -f"
    else
        echo "🛠️  Deploying in DEVELOPMENT mode..."
        docker-compose down --remove-orphans
        docker-compose up -d --build
        
        echo "✅ Development deployment complete!"
        echo "🌐 Access your application at: http://localhost:8080"
        echo "📊 Monitor with: docker-compose logs -f"
    fi
}

# Function to test deployment
test_deployment() {
    local port=$1
    echo "🧪 Testing deployment..."
    
    # Wait for container to be ready
    sleep 10
    
    # Test if application is accessible
    if curl -f -s "http://localhost:$port/subnets.html" > /dev/null; then
        echo "✅ Application is responding correctly"
        return 0
    else
        echo "❌ Application is not responding"
        return 1
    fi
}

# Function to show status
show_status() {
    echo "📊 Container Status:"
    docker ps --filter "name=subnet-calculator"
    
    echo ""
    echo "💾 Database Status:"
    if [ -f "subnets.db" ]; then
        echo "✅ Database file exists ($(du -h subnets.db | cut -f1))"
    else
        echo "⚠️  Database will be created on first use"
    fi
}

# Main script
case "${1:-dev}" in
    "prod"|"production")
        deploy "prod"
        if test_deployment 80; then
            show_status
        fi
        ;;
    "dev"|"development"|"")
        deploy "dev"
        if test_deployment 8080; then
            show_status
        fi
        ;;
    "test")
        echo "🧪 Running deployment tests..."
        deploy "dev"
        if test_deployment 8080; then
            echo "✅ All tests passed!"
            docker-compose down
        else
            echo "❌ Tests failed!"
            docker-compose logs
            docker-compose down
            exit 1
        fi
        ;;
    "stop")
        echo "🛑 Stopping all containers..."
        docker-compose down --remove-orphans
        docker-compose -f docker-compose.prod.yml down --remove-orphans
        echo "✅ All containers stopped"
        ;;
    "logs")
        echo "📋 Application logs:"
        docker-compose logs -f
        ;;
    "clean")
        echo "🧹 Cleaning up Docker resources..."
        docker-compose down --remove-orphans --volumes
        docker-compose -f docker-compose.prod.yml down --remove-orphans --volumes
        docker system prune -f
        echo "✅ Cleanup complete"
        ;;
    "help"|*)
        echo "Usage: $0 [command]"
        echo ""
        echo "Commands:"
        echo "  dev          Deploy in development mode (default)"
        echo "  prod         Deploy in production mode"
        echo "  test         Deploy, test, and cleanup"
        echo "  stop         Stop all containers"
        echo "  logs         Show application logs"
        echo "  clean        Clean up all Docker resources"
        echo "  help         Show this help message"
        echo ""
        echo "Examples:"
        echo "  $0 dev       # Start development server on port 8080"
        echo "  $0 prod      # Start production server on port 80"
        echo "  $0 test      # Run automated tests"
        ;;
esac