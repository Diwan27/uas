from flask import Flask, request, jsonify, render_template
import mysql.connector
from mysql.connector import Error
from datetime import datetime
from flask_cors import CORS
app = Flask(__name__)
CORS(app)

# Database configuration
db_config = {
    'host': 'localhost',
    'user': 'root',
    'password': '',  # Replace with your MySQL password
    'database': 'zakat_db'
}

# Function to establish database connection
def get_db_connection():
    try:
        connection = mysql.connector.connect(**db_config)
        if connection.is_connected():
            return connection
    except Error as e:
        print(f"Error connecting to MySQL: {e}")
        return None

# Route to serve the web interface
@app.route('/')
def index():
    return render_template('index.html')

# CRUD Operations for Pembayaran Table
@app.route('/pembayaran', methods=['POST'])
def create_pembayaran():
    data = request.get_json()
    nama = data.get('nama')
    jumlah_jiwa = data.get('jumlah_jiwa')
    jenis_zakat = data.get('jenis_zakat')
    metode_pembayaran = data.get('metode_pembayaran')
    total_bayar = data.get('total_bayar')
    nominal_dibayar = data.get('nominal_dibayar')
    kembalian = data.get('kembalian')
    keterangan = data.get('keterangan')
    tanggal_bayar = data.get('tanggal_bayar', datetime.now().strftime('%Y-%m-%d %H:%M:%S'))

    connection = get_db_connection()
    if connection is None:
        return jsonify({'error': 'Database connection failed'}), 500

    try:
        cursor = connection.cursor()
        query = """INSERT INTO pembayaran (nama, jumlah_jiwa, jenis_zakat, metode_pembayaran, 
                total_bayar, nominal_dibayar, kembalian, keterangan, tanggal_bayar) 
                VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s)"""
        values = (nama, jumlah_jiwa, jenis_zakat, metode_pembayaran, total_bayar, 
                 nominal_dibayar, kembalian, keterangan, tanggal_bayar)
        cursor.execute(query, values)
        connection.commit()
        return jsonify({'message': 'Pembayaran created successfully', 'id': cursor.lastrowid}), 201
    except Error as e:
        return jsonify({'error': str(e)}), 500
    finally:
        cursor.close()
        connection.close()

@app.route('/pembayaran', methods=['GET'])
def get_pembayaran():
    connection = get_db_connection()
    if connection is None:
        return jsonify({'error': 'Database connection failed'}), 500

    try:
        cursor = connection.cursor(dictionary=True)
        cursor.execute("SELECT * FROM pembayaran")
        pembayaran = cursor.fetchall()
        return jsonify(pembayaran), 200
    except Error as e:
        return jsonify({'error': str(e)}), 500
    finally:
        cursor.close()
        connection.close()

@app.route('/pembayaran/<int:id>', methods=['GET'])
def get_pembayaran_by_id(id):
    connection = get_db_connection()
    if connection is None:
        return jsonify({'error': 'Database connection failed'}), 500

    try:
        cursor = connection.cursor(dictionary=True)
        cursor.execute("SELECT * FROM pembayaran WHERE id = %s", (id,))
        pembayaran = cursor.fetchone()
        if pembayaran:
            return jsonify(pembayaran), 200
        return jsonify({'error': 'Pembayaran not found'}), 404
    except Error as e:
        return jsonify({'error': str(e)}), 500
    finally:
        cursor.close()
        connection.close()

@app.route('/pembayaran/<int:id>', methods=['PUT'])
def update_pembayaran(id):
    data = request.get_json()
    nama = data.get('nama')
    jumlah_jiwa = data.get('jumlah_jiwa')
    jenis_zakat = data.get('jenis_zakat')
    metode_pembayaran = data.get('metode_pembayaran')
    total_bayar = data.get('total_bayar')
    nominal_dibayar = data.get('nominal_dibayar')
    kembalian = data.get('kembalian')
    keterangan = data.get('keterangan')
    tanggal_bayar = data.get('tanggal_bayar')

    connection = get_db_connection()
    if connection is None:
        return jsonify({'error': 'Database connection failed'}), 500

    try:
        cursor = connection.cursor()
        query = """UPDATE pembayaran SET nama = %s, jumlah_jiwa = %s, jenis_zakat = %s, 
                metode_pembayaran = %s, total_bayar = %s, nominal_dibayar = %s, 
                kembalian = %s, keterangan = %s, tanggal_bayar = %s WHERE id = %s"""
        values = (nama, jumlah_jiwa, jenis_zakat, metode_pembayaran, total_bayar, 
                 nominal_dibayar, kembalian, keterangan, tanggal_bayar, id)
        cursor.execute(query, values)
        if cursor.rowcount == 0:
            return jsonify({'error': 'Pembayaran not found'}), 404
        connection.commit()
        return jsonify({'message': 'Pembayaran updated successfully'}), 200
    except Error as e:
        return jsonify({'error': str(e)}), 500
    finally:
        cursor.close()
        connection.close()

@app.route('/pembayaran/<int:id>', methods=['DELETE'])
def delete_pembayaran(id):
    connection = get_db_connection()
    if connection is None:
        return jsonify({'error': 'Database connection failed'}), 500

    try:
        cursor = connection.cursor()
        cursor.execute("DELETE FROM pembayaran WHERE id = %s", (id,))
        if cursor.rowcount == 0:
            return jsonify({'error': 'Pembayaran not found'}), 404
        connection.commit()
        return jsonify({'message': 'Pembayaran deleted successfully'}), 200
    except Error as e:
        return jsonify({'error': str(e)}), 500
    finally:
        cursor.close()
        connection.close()

# CRUD Operations for Beras Table
@app.route('/beras', methods=['POST'])
def create_beras():
    data = request.get_json()
    harga = data.get('harga')

    connection = get_db_connection()
    if connection is None:
        return jsonify({'error': 'Database connection failed'}), 500

    try:
        cursor = connection.cursor()
        query = "INSERT INTO beras (harga) VALUES (%s)"
        cursor.execute(query, (harga,))
        connection.commit()
        return jsonify({'message': 'Beras created successfully', 'id': cursor.lastrowid}), 201
    except Error as e:
        return jsonify({'error': str(e)}), 500
    finally:
        cursor.close()
        connection.close()

@app.route('/beras', methods=['GET'])
def get_beras():
    connection = get_db_connection()
    if connection is None:
        return jsonify({'error': 'Database connection failed'}), 500

    try:
        cursor = connection.cursor(dictionary=True)
        cursor.execute("SELECT * FROM beras")
        beras = cursor.fetchall()
        return jsonify(beras), 200
    except Error as e:
        return jsonify({'error': str(e)}), 500
    finally:
        cursor.close()
        connection.close()

@app.route('/beras/<int:id>', methods=['GET'])
def get_beras_by_id(id):
    connection = get_db_connection()
    if connection is None:
        return jsonify({'error': 'Database connection failed'}), 500

    try:
        cursor = connection.cursor(dictionary=True)
        cursor.execute("SELECT * FROM beras WHERE id = %s", (id,))
        beras = cursor.fetchone()
        if beras:
            return jsonify(beras), 200
        return jsonify({'error': 'Beras not found'}), 404
    except Error as e:
        return jsonify({'error': str(e)}), 500
    finally:
        cursor.close()
        connection.close()

@app.route('/beras/<int:id>', methods=['PUT'])
def update_beras(id):
    data = request.get_json()
    harga = data.get('harga')

    connection = get_db_connection()
    if connection is None:
        return jsonify({'error': 'Database connection failed'}), 500

    try:
        cursor = connection.cursor()
        query = "UPDATE beras SET harga = %s WHERE id = %s"
        cursor.execute(query, (harga, id))
        if cursor.rowcount == 0:
            return jsonify({'error': 'Beras not found'}), 404
        connection.commit()
        return jsonify({'message': 'Beras updated successfully'}), 200
    except Error as e:
        return jsonify({'error': str(e)}), 500
    finally:
        cursor.close()
        connection.close()

@app.route('/beras/<int:id>', methods=['DELETE'])
def delete_beras(id):
    connection = get_db_connection()
    if connection is None:
        return jsonify({'error': 'Database connection failed'}), 500

    try:
        cursor = connection.cursor()
        cursor.execute("DELETE FROM beras WHERE id = %s", (id,))
        if cursor.rowcount == 0:
            return jsonify({'error': 'Beras not found'}), 404
        connection.commit()
        return jsonify({'message': 'Beras deleted successfully'}), 200
    except Error as e:
        return jsonify({'error': str(e)}), 500
    finally:
        cursor.close()
        connection.close()

if __name__ == '__main__':
    app.run(debug=True)