#!/bin/bash
# Setup and run Playwright visual tests

echo "🔧 Setting up Playwright environment..."

# Check if Python 3 is available
if ! command -v python3 &> /dev/null; then
    echo "❌ Python 3 is not installed"
    exit 1
fi

# Check if pip is available
if ! command -v pip3 &> /dev/null; then
    echo "❌ pip3 is not installed"
    exit 1
fi

# Install Playwright if not already installed
echo "📦 Installing Playwright..."
pip3 install --user playwright > /dev/null 2>&1

# Install Playwright browsers
echo "🌐 Installing Playwright browsers (this may take a few minutes)..."
python3 -m playwright install chromium

# Make the test script executable
chmod +x visual_test.py

echo ""
echo "✅ Setup complete!"
echo ""
echo "🚀 Running visual tests..."
echo ""

# Run the tests
python3 visual_test.py

exit_code=$?

echo ""
if [ $exit_code -eq 0 ]; then
    echo "✅ Tests completed successfully"
else
    echo "❌ Tests failed with exit code $exit_code"
fi

exit $exit_code
