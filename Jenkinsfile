def REPO_NAME = env.JOB_NAME.split("/")[0]
def SUBMODULE_NAME = "IntegrationsBundle"

pipeline {
  options {
    skipDefaultCheckout()
    disableConcurrentBuilds()
  }
  agent {
    kubernetes {
      label 'mautic-hosted-build'
      inheritFrom 'with-mysql'
      containerTemplate {
        name 'hosted-tester'
        image 'us.gcr.io/mautic-ma/mautic_tester:master'
        ttyEnabled true
        command 'cat'
      }
    }
  }
  stages {
    stage('Download and combine') {
      steps {
        container('hosted-tester') {
          checkout changelog: false, poll: false, scm: [$class: 'GitSCM', branches: [[name: 'beta']], doGenerateSubmoduleConfigurations: false, extensions: [[$class: 'SubmoduleOption', disableSubmodules: false, parentCredentials: true, recursiveSubmodules: true]], submoduleCfg: [], userRemoteConfigs: [[credentialsId: '1a066462-6d24-4247-bef6-1da084c8f484', url: 'git@github.com:mautic-inc/mautic-cloud.git']]]
          sh('rm -r plugins/IntegrationsBundle || true; mkdir -p plugins/IntegrationsBundle && chmod 777 plugins/IntegrationsBundle')
          dir('plugins/IntegrationsBundle') {
            checkout scm
          }
        }
      }
    }
    stage('Build') {
      steps {
        container('hosted-tester') {
          ansiColor('xterm') {
            sh """
              composer install --ansi
            """
            dir('plugins/IntegrationsBundle') {
              sh("composer install --ansi")
            }
          }
        }
      }
    }
    stage('Test') {
      steps {
        container('hosted-tester') {
          ansiColor('xterm') {
            sh """
              mysql -h 127.0.0.1 -e 'CREATE DATABASE mautictest; CREATE USER travis@"%"; GRANT ALL on mautictest.* to travis@"%"; GRANT SUPER ON *.* TO travis@"%";'
              echo "<?php
              \\\$parameters = array(
                  'db_driver' => 'pdo_mysql',
                  'db_host' => '127.0.0.1',
                  'db_port' => 3306,
                  'db_name' => 'mautictest',
                  'db_user' => 'travis',
                  'db_password' => '',
                  'db_table_prefix' => '',
                  'hosted_plan' => 'pro'
              );" > app/config/local.php
              export SYMFONY_ENV="test"
              bin/phpunit -d memory_limit=2048M --bootstrap vendor/autoload.php --configuration plugins/IntegrationsBundle/phpunit.xml --fail-on-warning  --testsuite=all
            """
          }
        }
      }
    }
    stage('Static Analysis') {
      steps {
        container('hosted-tester') {
          ansiColor('xterm') {
            dir('plugins/IntegrationsBundle') {
              sh """
                composer run-script phpstan
              """
            }
          }
        }
      }
    }
    stage('Styling') {
      steps {
        container('hosted-tester') {
          ansiColor('xterm') {
            dir('plugins/IntegrationsBundle') {
              sh """
                vendor/bin/ecs check .
              """
            }
          }
        }
      }
    }
  }
  post {
    failure {
      script {
        if (BRANCH_NAME ==~ /^(beta|staging)$/) {
          slackSend (color: '#FF0000', message: "${REPO_NAME} failed build on branch ${env.BRANCH_NAME}. (${env.BUILD_URL}console)")
        }
        if (env.CHANGE_AUTHOR != null && !env.CHANGE_TITLE.contains("WIP")) {
          def githubToSlackMap = [
            'alanhartless':'alan.hartless',
            'anton-vlasenko':'anton.vlasenko',
            'dongilbert':'don.gilbert',
            'escopecz':'jan.linhart',
            'Gregy':'petr.gregor',
            'hluchas':'lukas.drahy',
            'lukassykora':'lukas.sykora',
            'mtshaw3':'mike.shaw',
            'pavel-hladik':'pavel.hladik'
          ]
          if (githubToSlackMap.("${env.CHANGE_AUTHOR}")) {
            slackSend (channel: "@"+"${githubToSlackMap.("${env.CHANGE_AUTHOR}")}", color: '#FF0000', message: "${REPO_NAME} failed build on ${env.BRANCH_NAME} (${env.CHANGE_TITLE})\nchange: ${env.CHANGE_URL}\nbuild: ${env.BUILD_URL}console")
          }
          else {
            slackSend (color: '#FF0000', message: "${REPO_NAME} failed build on ${env.BRANCH_NAME} (${env.CHANGE_TITLE})\nchange: ${env.CHANGE_URL}\nbuild: ${env.BUILD_URL}console\nsending alert to channel, there is no Github to Slack mapping for '${CHANGE_AUTHOR}'")
          }
        }
      }
    }
    fixed {
      script {
        if (BRANCH_NAME ==~ /^(beta|staging)$/) {
          slackSend (color: '#00FF00', message: "${REPO_NAME} build on branch ${env.BRANCH_NAME} is fixed. (${env.BUILD_URL}console)")
        }
      }
    }
  }
}
