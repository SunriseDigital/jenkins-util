<?php
//特にクラスにする意味は無いけどNamespace代わりに
///usr/bin/php /home/admin/jenkins-util/phpunit/create-jobs.php -u /home/source/sites/sdx/test/cases -s http://localhost:25252/ -j /home/admin/jenkins-cli.jar -i phpunit-start
class Phpunit_CreateJobs
{
  private static $xml_template = <<<EOF
<?xml version='1.0' encoding='UTF-8'?>
<project>
  <actions/>
  <description></description>
  <keepDependencies>false</keepDependencies>
  <properties/>
  <scm class="hudson.scm.NullSCM"/>
  <canRoam>true</canRoam>
  <disabled>false</disabled>
  <blockBuildWhenDownstreamBuilding>false</blockBuildWhenDownstreamBuilding>
  <blockBuildWhenUpstreamBuilding>false</blockBuildWhenUpstreamBuilding>
  <triggers/>
  <concurrentBuild>false</concurrentBuild>
  <builders>
    <hudson.tasks.Shell>
      <command>~/bin/phpunit --colors --stop-on-failure %s/%s</command>
    </hudson.tasks.Shell>
  </builders>
  <publishers/>
  <buildWrappers/>
</project>
EOF;

  private static $child_template = <<<EOF
<publishers>
    <hudson.tasks.BuildTrigger>
      <childProjects>%s</childProjects>
      <threshold>
        <name>FAILURE</name>
        <ordinal>2</ordinal>
        <color>RED</color>
        <completeBuild>true</completeBuild>
      </threshold>
    </hudson.tasks.BuildTrigger>
  </publishers>
EOF;

  private static $options;

  private static $options_setting = "u:s:j:i:p:";

  private static $default_options = array(
    'p' => 'phpunit'
  );

  private static $options_desc = array(
    'u' => 'phpunit test case dir',
    's' => 'jenkins server address',
    'j' => 'jenkins-cli.jar path',
    'i' => 'inital job name',
  );

  private static function getTests()
  {
    $phpunit_dir = self::getOption('u');
    $res_dir = opendir($phpunit_dir);

    $results = array();
    while( $file = readdir( $res_dir ) )
    {
      if($file != '.' && $file != '..')
      {
        $results[] = $file;
      }
    }

    return $results;
  }

  private static function toJobName($test_name)
  {
    //.phpで終わってたら取る
    if(strrchr($test_name, '.php') == '.php')
    {
      $test_name = substr($test_name, 0, strlen($test_name) - 4);
    }

    return sprintf('%s_%s', self::getJobPrefix(), $test_name);
  }

  private static function getJobPrefix()
  {
    return self::getOption('p');
  }

  private static function getJenkinsServer()
  {
    return self::getOption('s');
  }

  private static function getJenkinsCliJar()
  {
    return self::getOption('j');
  }

  private static function getPhpunitDir()
  {
    return self::getOption('u');
  }

  private static function getInitalJob()
  {
    return self::getOption('i');
  }

  private static function getJenkinsCommand()
  {
    $args = func_get_args();
    
    $command = $args[0];
    unset($args[0]);
    
    if($args)
    {
      $command = vsprintf($command, $args);
    }
    
    
    $jserver = self::getJenkinsServer();
    $cli_jar = self::getJenkinsCliJar();
    return sprintf('java -jar %s -s %s %s', $cli_jar, $jserver, $command);
  }

  private static function execCommand($command)
  {
    self::echoStdout($command);
    return trim(shell_exec($command));
  }

  private static function getJobSettings($job_name)
  {
    return self::execCommand(self::getJenkinsCommand('get-job %s', $job_name));
  }

  private static function getFirstChildJob($job_settings)
  {
    $job_settings = new SimpleXMLElement($job_settings);
    $child_job = $job_settings->publishers[0]->{'hudson.tasks.BuildTrigger'}[0];
    if($child_job)
    {
      return $child_job->childProjects[0]->__toString();;
    }
    else
    {
      false;
    }
  }

  public static function exec()
  {
    $inital_job = self::getInitalJob('i');
    $jobs = array();
    $tests = self::getTests();
    foreach($tests as $test)
    {
      $jobs[self::toJobName($test)] = $test;
    }

    //一番お尻のジョブを探し、かつ、登録されていないジョブを探す。
    self::echoStdout('Cheking the job list and unit tests...');
    $job_settings = self::getJobSettings($inital_job);
    $child_job = $inital_job;
    do
    {
      if(isset($jobs[$child_job]))
      {
        unset($jobs[$child_job]);
      }

      $job_settings = self::getJobSettings($child_job);
      $last_job = $child_job;
    }
    while($child_job = self::getFirstChildJob($job_settings));    
    $last_settings = $job_settings;


    $prev_job = $last_job;
    $prev_settings = $last_settings;
    $tem_xml_path = sprintf('/tmp/jenkins-util-%s-%s.xml', __CLASS__, uniqid(getmypid().'-'));
    foreach($jobs as $new_job => $test)
    {
      //前のジョブに子供を追加。
      self::echoStdout("Update the child job for {$prev_job}.");
      $prev_settings = str_replace('<publishers/>', sprintf(self::$child_template, $new_job), $prev_settings);
      file_put_contents($tem_xml_path, $prev_settings);
      self::execCommand(sprintf('cat "%s" | %s', $tem_xml_path, self::getJenkinsCommand('update-job %s', $prev_job)));

      //新しいジョブを追加。
      self::echoStdout("Create the new job {$new_job}.");
      $new_settings = sprintf(self::$xml_template, self::getPhpunitDir(), $test);
      file_put_contents($tem_xml_path, $new_settings);
      self::execCommand(sprintf('cat "%s" | %s', $tem_xml_path, self::getJenkinsCommand('create-job %s', $new_job)));

      //次の準備
      $prev_job = $new_job;
      $prev_settings = $new_settings;
    }

    if(is_file($tem_xml_path)){
      unlink($tem_xml_path);  
    }
  }

  private static function echoStdout($msg)
  {
    fputs(STDOUT, $msg.PHP_EOL);
  }

  private static function errorExit($msg, $code)
  {
    fputs(STDERR, $msg.PHP_EOL);
    exit($code);
  }

  private static function getOption($key)
  {
    if(self::$options === null)
    {
      self::$options = getopt(self::$options_setting);
    }
    

    if(isset(self::$options[$key]))
    {
      return self::$options[$key];
    }
    else if(isset(self::$default_options[$key]))
    {
      return self::$default_options[$key];
    }
    else
    {
      self::errorExit('Missing option -'.$key.' ['.self::$options_desc[$key].']', 1);
    }
  }
}


Phpunit_CreateJobs::exec();