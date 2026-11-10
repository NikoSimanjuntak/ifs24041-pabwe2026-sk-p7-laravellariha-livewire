pipeline {
    agent {
        dockerfile {
            filename 'Dockerfile.ci'
            args '-u root'
        }
    }

    options {
        timestamps()
        disableConcurrentBuilds()
    }

    environment {
        APP_ENV = 'testing'
    }

    stages {
        stage('Install Composer') {
            steps {
                sh 'php -v && php -m | grep -E "^(gd|zip|mbstring|pdo_mysql|pcov)$"'
                sh 'composer install --no-interaction --prefer-dist --no-progress'
                sh 'cp -n .env.example .env && php artisan key:generate --ansi'
            }
        }

        stage('Composer Audit') {
            steps {
                // Gagal (exit code != 0) bila ada advisory kerentanan.
                sh 'composer audit --no-interaction'
            }
        }

        stage('Build Frontend') {
            steps {
                sh 'npm ci --ignore-scripts || npm install --ignore-scripts'
                sh 'npm run build'
            }
        }

        stage('PHPUnit + Coverage 100%') {
            steps {
                sh '''#!/bin/bash
                    set -eo pipefail
                    export XDEBUG_MODE=coverage
                    php artisan config:clear
                    vendor/bin/phpunit --coverage-text --coverage-clover=coverage.xml --log-junit=junit.xml | tee coverage.txt

                    # Gerbang coverage: Classes / Methods / Lines wajib 100%
                    for metric in Classes Methods Lines; do
                        if ! grep -Eq "^\\s*${metric}:\\s+100\\.00%" coverage.txt; then
                            echo "GAGAL: coverage ${metric} kurang dari 100%"
                            exit 1
                        fi
                    done
                '''
            }
            post {
                always {
                    junit allowEmptyResults: true, testResults: 'junit.xml'
                }
            }
        }

        stage('Trivy Scan') {
            steps {
                sh '''#!/bin/bash
                    set -eo pipefail
                    mkdir -p trivy
                    trivy fs --cache-dir trivy/cache --skip-dirs vendor,node_modules,storage,trivy \
                        --scanners vuln,secret --severity HIGH,CRITICAL --exit-code 1 \
                        --format table --output trivy/report.txt .
                '''
            }
            post {
                always {
                    archiveArtifacts artifacts: 'trivy/report.txt', allowEmptyArchive: true
                }
            }
        }

        stage('Package') {
            steps {
                // Tanpa vendor/ dan node_modules/; .env.example tetap disertakan.
                sh '''#!/bin/bash
                    set -eo pipefail
                    rm -f latest-app.zip
                    zip -rq latest-app.zip . \
                        -x "vendor/*" "node_modules/*" ".git/*" ".env" "trivy/*" \
                           "coverage*" "junit.xml" "events.txt" "phpunit_*.txt" ".phpunit.cache/*" "latest-app.zip"
                '''
                archiveArtifacts artifacts: 'latest-app.zip', fingerprint: true
            }
        }
    }

    post {
        always {
            archiveArtifacts artifacts: 'coverage.txt', allowEmptyArchive: true
            cleanWs()
        }
    }
}
