pipeline {
    agent any

    stages {
        stage ('Build Image'){
            steps {
                script {    
                    dockerapp = docker.build('fabio/api-tenant', '-f ./vendor/laravel/sail/runtimes/8.1/Dockerfile')
                }
                echo 'Iniciando a pipeline'
            }
        }
    }
}

