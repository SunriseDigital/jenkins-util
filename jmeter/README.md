## 概要
ディレクトリを指定して、全てjmxファイルを順番に実行し、一つでもリクエストにエラーが有った場合、エラー内容を出力しExitCode`1`で終了します。

エラーハンドリング処理を利用するにはjmeter側の設定でログファイルの書き出しを設定する必要があります。このスクリプトでは一つのjmxファイルに対して一つのログを想定していますので、全てのスレッドで共通の`View Results Tree`を一つ作成して下さい。

<img src="images/create-listener.jpg" alt="View Results Treeの作成">

jmeterをコマンドラインから実行する時、jmxに引数から値を与えることが可能です。

```shell
/bin/sh jmeter -n -Jname="value" sample.jmx
```

`-J（大文字）`の後に続けて名前と値を指定します。この`value`はjmx内で`${__P(name)}`で参照可能です。

jmxに追加した`View Results Tree`のログ書き出しをONにして、ファイル名は引数から受け取るよう`${__P(jutillog)}`に設定して下さい。ログのファイル名は同時に実行されても被らないようにスクリプトから生成して渡されます。

ログの出力内容は`Configureボタンで設定可能です。どのような内容でもこのスクリプトの動作には影響ありませんが、沢山出力すると見づらいので下記のように設定することをおすすめします。

<img src="images/log-setting.jpg" alt="View Results Treeの設定">

### オプションと引数
```sh
./jmeter.sh -j /home/admin/apache-jmeter-2.10 -o '-Jdomain="www.example.com"' /home/admin/jmeter
```

#### 引数
実行したいjmxが入っているディレクトリを指定します。直下だけでなく深い階層も検索します。

#### jオプション
jmeterのホームディレクトリを指定します。このディレクトリから`bin/jmeter`を実行します。

#### oオプション
jmeterに渡すオプションを指定します。jmxファイルに渡したい引数がある時に利用して下さい。このオプションはそのまま引数に文字列で渡され`eval`で実行されますので、WEBなど不特定多数が値を渡せるようなコードを書く時は注意して下さい。