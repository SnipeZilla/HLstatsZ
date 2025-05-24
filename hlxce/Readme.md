
# HLstatsX Community Edition Daemon Kubernetes Deployment

This project provides a guide to deploying the HLstatsX Daemon in a Kubernetes cluster using the public Docker image. It is part of the [HLstatsX Community Edition](https://github.com/NiDE-gg/hlstatsx-community-edition) repository.

## Prerequisites

- A functioning Kubernetes environment.
- Docker for managing images locally (if needed).
- A database for HLstatsX (MySQL/MariaDB).
- Access to a Docker image registry to pull the image or build your own.

## File Structure

The Kubernetes deployment files are located in the following directory:

```bash
/scripts/deploy/
```

### Key Files

- `Deployment.yml`: The main deployment for the HLstatsX Daemon.
- `Deployment-jobs.yml`: Configuration file for a **CronJob** that runs the awards task.

## Deployment Steps

### 1. Clone the Repository

Start by cloning the GitHub repository and navigating to the directory containing the deployment files.

```bash
git clone https://github.com/NiDE-gg/hlstatsx-community-edition.git
cd hlstatsx-community-edition/scripts/deploy
```

### 2. Use the Docker Image

The Docker image is publicly available at the following address: `ghcr.io/nide-gg/hlstatsx-deamon:latest`. It is already referenced in the YAML files, so there is no need to build it manually unless you want to customize the image.

If you wish to customize the image, you can build and push your own Docker image as follows:

#### Build Your Custom Docker Image

```bash
docker build -t <your-docker-registry>/hlstatsx-daemon:latest .
docker push <your-docker-registry>/hlstatsx-daemon:latest
```

Then, modify the `Deployment.yml` and `Deployment-jobs.yml` files to use your custom image.

### 3. Set Environment Variables

The deployment depends on several environment variables for the database configuration, which you need to define in Kubernetes:

- `DB_NAME`: The HLstatsX database name.
- `DB_PASSWORD`: The database password.
- `DB_USERNAME`: The username for accessing the database.
- `DB_HOST`: The database host address.

You can manage these environment variables securely using a **ConfigMap** or a **Secret** in Kubernetes.

### 4. Deploy to Kubernetes

Once the Docker image and environment variables are set, you can deploy the HLstatsX Daemon and CronJobs to your Kubernetes cluster.

#### Deploy the HLstatsX Daemon

To deploy the main application, run the following command:

```bash
kubectl apply -f Deployment.yml
```

#### Deploy the Awards CronJob

To deploy the CronJob that handles the HLstatsX awards:

```bash
kubectl apply -f Deployment-jobs.yml
```

### 5. Verify Deployments

Use the following commands to verify that everything is running correctly.

#### Verify the Daemon Deployment

```bash
kubectl get deployments
kubectl get pods
```

#### Verify the CronJob

```bash
kubectl get cronjobs
kubectl get jobs
```

### 6. Logs and Debugging

To view logs from the deployment or CronJobs, use:

```bash
kubectl logs <pod-name>
```

Replace `<pod-name>` with the actual name of the running pod.

## YAML Files

### `Deployment.yml`

```yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: hlstatx
  labels:
    app: hlstatx
spec:
  selector:
    matchLabels:
      app: hlstatx
  revisionHistoryLimit: 10
  replicas: 1
  strategy:
    type: Recreate
  template:
    metadata:
      labels:
        app: hlstatx
    spec:
      hostNetwork: false
      containers:
        - name: hlstatx-daemon
          image: ghcr.io/nide-gg/hlstatsx-deamon:latest
          imagePullPolicy: Always
          ports:
            - name: hlstatx-daemon
              containerPort: 32578
              hostPort: 32578
              protocol: UDP
          env:
            - name: DB_NAME
              value: $DB_NAME
            - name: DB_PASSWORD
              value: $DB_PASSWORD
            - name: DB_USERNAME
              value: $DB_USERNAME
            - name: DB_HOST
              value: $DB_HOST
```

### `Deployment-jobs.yml`

```yaml
apiVersion: batch/v1beta1
kind: CronJob
metadata:
  name: hlstatx-daemon-awards
spec:
  concurrencyPolicy: Allow
  failedJobsHistoryLimit: 1
  jobTemplate:
    metadata:
      creationTimestamp: null
    spec:
      activeDeadlineSeconds: 300
      template:
        spec:
          containers:
          - command:
            - docker-hlxce-awards
            env:
            - name: DB_HOST
              value: $DB_HOST
            - name: DB_NAME
              value: $DB_NAME
            - name: DB_PASSWORD
              value: $DB_PASSWORD
            - name: DB_USERNAME
              value: $DB_USERNAME
            image: ghcr.io/nide-gg/hlstatsx-deamon:latest
            imagePullPolicy: Always
            name: hlstatx-daemon-awards
            resources: {}
            securityContext:
              allowPrivilegeEscalation: false
              capabilities: {}
              privileged: false
              readOnlyRootFilesystem: false
              runAsNonRoot: false
            stdin: true
            terminationMessagePath: /dev/termination-log
            terminationMessagePolicy: File
            tty: true
          dnsPolicy: ClusterFirst
          restartPolicy: Never
          schedulerName: default-scheduler
          securityContext: {}
          terminationGracePeriodSeconds: 30
  schedule: "0 23 * * *"
  successfulJobsHistoryLimit: 3
  suspend: false
```

## Additional Notes

- The `Deployment.yml` file uses a **Recreate** deployment strategy, meaning all old instances are stopped before new instances are created during an update.
- The CronJob is scheduled to run daily at 11:00 PM. You can modify the frequency by adjusting the `schedule` line in `Deployment-jobs.yml` (e.g., `0 12 * * *` for 12:00 PM daily).
