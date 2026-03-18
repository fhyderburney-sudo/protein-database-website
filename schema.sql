DROP TABLE IF EXISTS motif_hits;
DROP TABLE IF EXISTS analysis_results;
DROP TABLE IF EXISTS run_files;
DROP TABLE IF EXISTS proteins;
DROP TABLE IF EXISTS runs;

CREATE TABLE runs (
    run_id INT AUTO_INCREMENT PRIMARY KEY,
    user_forname VARCHAR(100) NOT NULL,
    user_surname VARCHAR(100) NOT NULL,
    protein_family VARCHAR(255) NOT NULL,
    taxon_query VARCHAR(255) NOT NULL,
    ncbi_query TEXT,
    run_type ENUM('example', 'user') NOT NULL DEFAULT 'user',
    status ENUM('pending', 'complete', 'failed') NOT NULL DEFAULT 'pending',
    sequence_count INT DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    notes TEXT
);

CREATE TABLE proteins (
    protein_id INT AUTO_INCREMENT PRIMARY KEY,
    run_id INT NOT NULL,
    accession VARCHAR(100) NOT NULL,
    protein_name VARCHAR(255),
    organism VARCHAR(255),
    taxon_id INT,
    fasta_header TEXT,
    sequence MEDIUMTEXT NOT NULL,
    seq_length INT NOT NULL,
    source_db VARCHAR(50) DEFAULT 'NCBI',
    retrieved_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (run_id) REFERENCES runs(run_id) ON DELETE CASCADE
);

CREATE TABLE analysis_results (
    analysis_id INT AUTO_INCREMENT PRIMARY KEY,
    run_id INT NOT NULL,
    analysis_type VARCHAR(100) NOT NULL,
    summary_text TEXT,
    output_file VARCHAR(255),
    plot_file VARCHAR(255),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (run_id) REFERENCES runs(run_id) ON DELETE CASCADE
);

CREATE TABLE motif_hits (
    motif_hit_id INT AUTO_INCREMENT PRIMARY KEY,
    run_id INT NOT NULL,
    protein_id INT NOT NULL,
    motif_accession VARCHAR(50),
    motif_name VARCHAR(255),
    start_pos INT,
    end_pos INT,
    description TEXT,
    FOREIGN KEY (run_id) REFERENCES runs(run_id) ON DELETE CASCADE,
    FOREIGN KEY (protein_id) REFERENCES proteins(protein_id) ON DELETE CASCADE
);

CREATE TABLE run_files (
    file_id INT AUTO_INCREMENT PRIMARY KEY,
    run_id INT NOT NULL,
    file_type VARCHAR(100) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    description TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (run_id) REFERENCES runs(run_id) ON DELETE CASCADE
);
