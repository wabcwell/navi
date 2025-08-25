/**
 * 数据库初始化脚本
 * 使用综合配置文件
 */

const mysql = require('mysql2/promise');
const config = require('./config');

async function initializeDatabase() {
  console.log('=== 数据库初始化 ===\n');

  try {
    // 获取当前配置
    const dbConfig = config.utils.getDatabaseConfig();
    
    // 创建数据库连接（不指定数据库）
    const connection = await mysql.createConnection({
      host: dbConfig.host,
      port: dbConfig.port,
      user: dbConfig.username,
      password: dbConfig.password
    });

    console.log('✅ 已连接到MySQL服务器');

    // 直接连接到已存在的数据库
    console.log(`🔄 连接到数据库: ${dbConfig.database}...`);
    await connection.query(`USE ${dbConfig.database}`);
    console.log(`✅ 已连接到数据库: ${dbConfig.database}`);

    // 创建表
    console.log('🔄 创建数据表...');
    const createTableStatements = config.database.initSQL.createTables.split(';').filter(sql => sql.trim());
    
    for (const sql of createTableStatements) {
      if (sql.trim()) {
        await connection.query(sql);
      }
    }
    console.log('✅ 数据表创建成功');

    // 插入示例数据
    console.log('🔄 插入示例数据...');
    const insertStatements = config.database.initSQL.insertSampleData.split(';').filter(sql => sql.trim());
    
    for (const sql of insertStatements) {
      if (sql.trim()) {
        try {
          await connection.query(sql);
        } catch (error) {
          // 忽略重复插入错误
          if (!error.message.includes('Duplicate entry')) {
            console.warn('⚠️  插入警告:', error.message);
          }
        }
      }
    }
    console.log('✅ 示例数据插入成功');

    // 验证数据
    console.log('\n📊 验证数据...');
    
    const [categories] = await connection.query('SELECT COUNT(*) as count FROM categories');
    console.log(`📋 分类数量: ${categories[0].count}`);
    
    const [links] = await connection.query('SELECT COUNT(*) as count FROM navigation_links');
    console.log(`🔗 导航链接数量: ${links[0].count}`);
    
    const [categoryList] = await connection.query('SELECT name FROM categories ORDER BY sort_order');
    console.log('📂 分类列表:', categoryList.map(c => c.name).join(', '));

    await connection.end();
    console.log('\n🎉 数据库初始化完成！');
    console.log('💡 现在可以运行: npm run db-test 来测试连接');

  } catch (error) {
    console.error('❌ 初始化失败:', error.message);
    process.exit(1);
  }
}

// 重置数据库（删除所有表）
async function resetDatabase() {
  console.log('=== 数据库重置 ===\n');

  try {
    const config = DB_CONFIG.utils.getCurrentConfig();
    
    const connection = await mysql.createConnection({
      host: config.host,
      port: config.port,
      user: config.username,
      password: config.password,
      database: config.database
    });

    console.log('⚠️  正在重置数据库...');
    
    // 删除表（按依赖顺序）
    const tables = ['navigation_links', 'categories', 'user_preferences'];
    
    for (const table of tables) {
      try {
        await connection.query(`DROP TABLE IF EXISTS ${table}`);
        console.log(`🗑️  已删除表: ${table}`);
      } catch (error) {
        console.warn(`⚠️  删除表 ${table} 失败:`, error.message);
      }
    }

    await connection.end();
    console.log('✅ 数据库重置完成');
    console.log('💡 现在可以运行: npm run db-init 重新初始化');

  } catch (error) {
    console.error('❌ 重置失败:', error.message);
    process.exit(1);
  }
}

// 显示数据库状态
async function showDatabaseStatus() {
  console.log('=== 数据库状态 ===\n');

  try {
    const config = DB_CONFIG.utils.getCurrentConfig();
    
    const connection = await mysql.createConnection({
      host: config.host,
      port: config.port,
      user: config.username,
      password: config.password,
      database: config.database
    });

    // 检查表是否存在
    const [tables] = await connection.execute(`
      SELECT table_name, table_rows 
      FROM information_schema.tables 
      WHERE table_schema = ?
    `, [config.database]);

    if (tables.length === 0) {
      console.log('📭 数据库为空，需要初始化');
      console.log('💡 运行: npm run db-init');
    } else {
      console.log(`📊 发现 ${tables.length} 张表:`);
      tables.forEach(table => {
        console.log(`   ${table.table_name}: ${table.table_rows} 行`);
      });
    }

    await connection.end();

  } catch (error) {
    console.error('❌ 获取状态失败:', error.message);
  }
}

// 主函数
async function main() {
  const args = process.argv.slice(2);
  
  if (args.includes('--reset')) {
    await resetDatabase();
  } else if (args.includes('--status')) {
    await showDatabaseStatus();
  } else {
    await initializeDatabase();
  }
}

// 执行主函数
if (require.main === module) {
  main().catch(console.error);
}

module.exports = {
  initializeDatabase,
  resetDatabase,
  showDatabaseStatus
};