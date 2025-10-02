# Subnet Calculator - Quick Deployment Script (PowerShell)
# This script deploys the subnet calculator using Docker

param(
    [Parameter(Position=0)]
    [ValidateSet("dev", "development", "prod", "production", "test", "stop", "logs", "clean", "help")]
    [string]$Command = "dev"
)

Write-Host "🌐 Subnet Calculator - Docker Deployment" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan

# Check if Docker is installed
try {
    docker --version | Out-Null
    Write-Host "✅ Docker is available" -ForegroundColor Green
} catch {
    Write-Host "❌ Docker is not installed. Please install Docker Desktop first." -ForegroundColor Red
    Write-Host "   Visit: https://docs.docker.com/desktop/windows/" -ForegroundColor Yellow
    exit 1
}

# Check if Docker Compose is installed
try {
    docker-compose --version | Out-Null
    Write-Host "✅ Docker Compose is available" -ForegroundColor Green
} catch {
    Write-Host "❌ Docker Compose is not available. Please ensure Docker Desktop is running." -ForegroundColor Red
    exit 1
}

function Deploy-Application {
    param([string]$Mode)
    
    if ($Mode -eq "prod" -or $Mode -eq "production") {
        Write-Host "🚀 Deploying in PRODUCTION mode..." -ForegroundColor Yellow
        docker-compose -f docker-compose.prod.yml down --remove-orphans
        docker-compose -f docker-compose.prod.yml up -d --build
        
        Write-Host "✅ Production deployment complete!" -ForegroundColor Green
        Write-Host "🌐 Access your application at: http://localhost" -ForegroundColor Cyan
        Write-Host "📊 Monitor with: docker-compose -f docker-compose.prod.yml logs -f" -ForegroundColor Gray
    } else {
        Write-Host "🛠️  Deploying in DEVELOPMENT mode..." -ForegroundColor Yellow
        docker-compose down --remove-orphans
        docker-compose up -d --build
        
        Write-Host "✅ Development deployment complete!" -ForegroundColor Green
        Write-Host "🌐 Access your application at: http://localhost:8080" -ForegroundColor Cyan
        Write-Host "📊 Monitor with: docker-compose logs -f" -ForegroundColor Gray
    }
}

function Test-Deployment {
    param([int]$Port)
    Write-Host "🧪 Testing deployment..." -ForegroundColor Yellow
    
    # Wait for container to be ready
    Start-Sleep 10
    
    try {
        $response = Invoke-WebRequest -Uri "http://localhost:$Port/subnets.html" -UseBasicParsing -TimeoutSec 30
        if ($response.StatusCode -eq 200) {
            Write-Host "✅ Application is responding correctly" -ForegroundColor Green
            return $true
        }
    } catch {
        Write-Host "❌ Application is not responding: $($_.Exception.Message)" -ForegroundColor Red
        return $false
    }
}

function Show-Status {
    Write-Host "📊 Container Status:" -ForegroundColor Cyan
    docker ps --filter "name=subnet-calculator"
    
    Write-Host ""
    Write-Host "💾 Database Status:" -ForegroundColor Cyan
    if (Test-Path "subnets.db") {
        $dbSize = (Get-Item "subnets.db").Length / 1KB
        Write-Host "✅ Database file exists ($([math]::Round($dbSize, 2)) KB)" -ForegroundColor Green
    } else {
        Write-Host "⚠️  Database will be created on first use" -ForegroundColor Yellow
    }
}

# Main script logic
switch ($Command) {
    { $_ -in @("prod", "production") } {
        Deploy-Application "prod"
        if (Test-Deployment 80) {
            Show-Status
        }
    }
    
    { $_ -in @("dev", "development") } {
        Deploy-Application "dev"
        if (Test-Deployment 8080) {
            Show-Status
        }
    }
    
    "test" {
        Write-Host "🧪 Running deployment tests..." -ForegroundColor Yellow
        Deploy-Application "dev"
        if (Test-Deployment 8080) {
            Write-Host "✅ All tests passed!" -ForegroundColor Green
            docker-compose down
        } else {
            Write-Host "❌ Tests failed!" -ForegroundColor Red
            Write-Host "Container logs:" -ForegroundColor Gray
            docker-compose logs
            docker-compose down
            exit 1
        }
    }
    
    "stop" {
        Write-Host "🛑 Stopping all containers..." -ForegroundColor Yellow
        docker-compose down --remove-orphans
        docker-compose -f docker-compose.prod.yml down --remove-orphans
        Write-Host "✅ All containers stopped" -ForegroundColor Green
    }
    
    "logs" {
        Write-Host "📋 Application logs:" -ForegroundColor Cyan
        docker-compose logs -f
    }
    
    "clean" {
        Write-Host "🧹 Cleaning up Docker resources..." -ForegroundColor Yellow
        docker-compose down --remove-orphans --volumes
        docker-compose -f docker-compose.prod.yml down --remove-orphans --volumes
        docker system prune -f
        Write-Host "✅ Cleanup complete" -ForegroundColor Green
    }
    
    default {
        Write-Host "Usage: .\deploy.ps1 [command]" -ForegroundColor White
        Write-Host ""
        Write-Host "Commands:" -ForegroundColor Cyan
        Write-Host "  dev          Deploy in development mode (default)" -ForegroundColor White
        Write-Host "  prod         Deploy in production mode" -ForegroundColor White
        Write-Host "  test         Deploy, test, and cleanup" -ForegroundColor White
        Write-Host "  stop         Stop all containers" -ForegroundColor White
        Write-Host "  logs         Show application logs" -ForegroundColor White
        Write-Host "  clean        Clean up all Docker resources" -ForegroundColor White
        Write-Host "  help         Show this help message" -ForegroundColor White
        Write-Host ""
        Write-Host "Examples:" -ForegroundColor Cyan
        Write-Host "  .\deploy.ps1 dev       # Start development server on port 8080" -ForegroundColor Gray
        Write-Host "  .\deploy.ps1 prod      # Start production server on port 80" -ForegroundColor Gray
        Write-Host "  .\deploy.ps1 test      # Run automated tests" -ForegroundColor Gray
    }
}