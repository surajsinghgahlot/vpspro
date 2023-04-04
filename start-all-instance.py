import boto3

ec2_client = boto3.client('ec2')

def lambda_handler(event, context):
    F1={"Name": "instance-state-name", "Values":["stopped"]}
    id_response=ec2_client.describe_instances(Filters=[F1])
    for i in id_response['Reservations']:
        for j in i['Instances']:
            id=(j['InstanceId'])
            ec2_client.start_instances(InstanceIds=[id])