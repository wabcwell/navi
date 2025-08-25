/**
 * 数据库连接测试脚本
 * 使用综合配置文件
 */

const config = require('./config');
const mysql = require('mysql2/promise');

async function testDatabaseConnection() {
  console.log('=== 数据库连接测试 ===\n');
  
  try {
    const dbConfig = config.utils.getDatabaseConfig();
    const connection = await mysql.createConnection({
      host: dbConfig.host,
      port: dbConfig.port,
      user: dbConfig.username,
      password: dbConfig.password,
      database: dbConfig.database
    });

    await connection.execute('SELECT 1');
    await connection.end();

    console.log('✅ 连接成功！');
    console.log('📊 配置信息：');
    console.log(`   主机: ${dbConfig.host}:${dbConfig.port}`);
    console.log(`   数据库: ${dbConfig.database}`);
    console.log(`   用户: ${dbConfig.username}`);
    
    // 显示当前环境
    const env = config.utils.getCurrentEnv();
    console.log(`   环境: ${env}`);
    
    // 显示连接字符串
    console.log('\n🔗 连接字符串：');
    console.log(config.database.connectionStrings[env]);
    
  } catch (error) {
    console.error('❌ 连接失败：', error.message);
  }
}

// 显示配置摘要
function showConfigSummary() {
  console.log('\n=== 配置摘要 ===');
  
  const env = config.utils.getCurrentEnv();
  const dbConfig = config.utils.getDatabaseConfig();
  
  console.log(`当前环境: ${env}`);
  console.log(`主机: ${dbConfig.host}:${dbConfig.port}`);
  console.log(`数据库: ${dbConfig.database}`);
  console.log(`连接池: ${dbConfig.pool.min}-${dbConfig.pool.max}`);
  console.log(`日志: ${dbConfig.logging ? '启用' : '禁用'}`);
  
  console.log('\n=== 支持的环境 ===');
  Object.keys(config.database.mysql).forEach(env => {
    const cfg = config.database.mysql[env];
    console.log(`- ${env}: ${cfg.host}:${cfg.port}/${cfg.database}`);
  });
}

// 显示数据库表结构
function showTableSchema() {
  console.log('\n=== 数据库表结构 ===');
  
  Object.keys(config.database.tables).forEach(tableName => {
    const table = config.database.tables[tableName];
    console.log(`\n📋 ${table.name}:`);
    
    Object.keys(table.columns).forEach(columnName => {
      const column = table.columns[columnName];
      let desc = `${columnName}: ${column.type}`;
      
      if (column.primaryKey) desc += ' (主键)';
      if (column.autoIncrement) desc += ' (自增)';
      if (column.allowNull === false) desc += ' (必填)';
      if (column.unique) desc += ' (唯一)';
      if (column.defaultValue !== undefined) desc += ` (默认: ${column.defaultValue})`;
      
      console.log(`   ${desc}`);
    });
  });
}

// 显示初始化SQL
function showInitSQL() {
  console.log('\n=== 初始化SQL ===');
  console.log('创建数据库：');
  console.log(config.database.initSQL.createDatabase);
  
  console.log('\n创建表：');
  console.log(config.database.initSQL.createTables);
  
  console.log('\n示例数据：');
  console.log(config.database.initSQL.insertSampleData);
}

// 主函数
async function main() {
  const args = process.argv.slice(2);
  
  if (args.includes('--summary')) {
    showConfigSummary();
  } else if (args.includes('--schema')) {
    showTableSchema();
  } else if (args.includes('--sql')) {
    showInitSQL();
  } else {
    await testDatabaseConnection();
  }
}

// 执行主函数
if (require.main === module) {
  main().catch(console.error);
}

module.exports = {
  testDatabaseConnection,
  showConfigSummary,
  showTableSchema,
  showInitSQL
};